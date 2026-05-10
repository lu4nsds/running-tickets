import { Injectable, Logger, InternalServerErrorException, BadRequestException } from '@nestjs/common';
import makeWASocket, {
  DisconnectReason,
  useMultiFileAuthState,
  WASocket,
  makeCacheableSignalKeyStore,
  fetchLatestBaileysVersion,
  Browsers,
} from '@whiskeysockets/baileys';
import { Boom } from '@hapi/boom';
import Redis from 'ioredis';
import * as fs from 'fs';
import * as path from 'path';
import pino from 'pino';

type SessionStatus = 'connecting' | 'open' | 'closed' | 'error';

interface SessionState {
  socket: WASocket | null;
  status: SessionStatus;
  qr: string | null;
}

const SESSIONS_KEY = 'running-tickets:whatsapp:sessions';
const AUTH_DIR = path.join(process.cwd(), '.whatsapp-auth');
const CONNECT_TIMEOUT_MS = parseInt(process.env.WHATSAPP_CONNECT_TIMEOUT_MS ?? '30000', 10);

const logger = pino({ level: 'silent' });

@Injectable()
export class AppService {
  private readonly log = new Logger(AppService.name);
  private readonly redis: Redis;
  private readonly sessions = new Map<string, SessionState>();

  constructor() {
    this.redis = new Redis(process.env.REDIS_URL ?? 'redis://localhost:6379');
    fs.mkdirSync(AUTH_DIR, { recursive: true });
  }

  // ── Public API ───────────────────────────────────────────────────────────────

  async connect(tenantId: string): Promise<{ status: SessionStatus; qr: string | null }> {
    const existing = this.sessions.get(tenantId);

    if (existing?.status === 'open') {
      return { status: 'open', qr: null };
    }

    if (existing?.status === 'connecting') {
      return { status: 'connecting', qr: existing.qr };
    }

    await this.startSession(tenantId);

    // Aguarda QR ou conexão (até 20s para dar tempo ao fetchLatestBaileysVersion)
    await this.waitFor(tenantId, (s) => s.qr !== null || s.status === 'open', 20_000);

    const state = this.sessions.get(tenantId);
    return { status: state?.status ?? 'error', qr: state?.qr ?? null };
  }

  async status(tenantId: string): Promise<{ status: SessionStatus; qr: string | null }> {
    const state = this.sessions.get(tenantId);
    if (!state) return { status: 'closed', qr: null };
    return { status: state.status, qr: state.qr };
  }

  async disconnect(tenantId: string): Promise<void> {
    const state = this.sessions.get(tenantId);
    if (state?.socket) {
      await state.socket.logout().catch(() => {});
      state.socket.end(undefined);
    }
    this.sessions.delete(tenantId);
    await this.redis.srem(SESSIONS_KEY, tenantId);
    this.removeAuthFiles(tenantId);
    this.log.log(`Tenant ${tenantId} disconnected`);
  }

  async send(tenantId: string, phone: string, message: string): Promise<string> {
    const normalized = this.normalizePhone(phone);
    const jid = `${normalized}@s.whatsapp.net`;

    let state = this.sessions.get(tenantId);
    if (!state || state.status !== 'open') {
      await this.startSession(tenantId);
      await this.waitFor(tenantId, (s) => s.status === 'open', CONNECT_TIMEOUT_MS);
      state = this.sessions.get(tenantId);
    }

    if (!state?.socket || state.status !== 'open') {
      throw new InternalServerErrorException('WhatsApp session not connected');
    }

    const [result] = await state.socket.onWhatsApp(normalized);
    if (!result?.exists) {
      throw new BadRequestException(`Phone ${normalized} does not have WhatsApp`);
    }

    await state.socket.sendMessage(jid, { text: message });
    return jid;
  }

  async reconnectAll(): Promise<void> {
    const tenants = await this.redis.smembers(SESSIONS_KEY);
    this.log.log(`Reconnecting ${tenants.length} tenant(s)...`);

    for (const tenantId of tenants) {
      // Só reconecta se existirem credenciais válidas salvas
      const credsFile = path.join(AUTH_DIR, tenantId, 'creds.json');
      if (!fs.existsSync(credsFile)) {
        this.log.warn(`Tenant ${tenantId}: no credentials found, skipping auto-reconnect`);
        await this.redis.srem(SESSIONS_KEY, tenantId);
        continue;
      }

      await this.startSession(tenantId).catch((e) =>
        this.log.warn(`Failed to reconnect ${tenantId}: ${e.message}`),
      );
    }
  }

  // ── Private ──────────────────────────────────────────────────────────────────

  private async startSession(tenantId: string): Promise<void> {
    const authDir = path.join(AUTH_DIR, tenantId);
    fs.mkdirSync(authDir, { recursive: true });

    const { state, saveCreds } = await useMultiFileAuthState(authDir);

    this.sessions.set(tenantId, { socket: null, status: 'connecting', qr: null });

    // Busca a versão atual do protocolo WhatsApp Web — essencial para evitar rejeição
    const { version } = await fetchLatestBaileysVersion();
    this.log.log(`Using WA version: ${version.join('.')}`);

    const sock = makeWASocket({
      version,
      auth: {
        creds: state.creds,
        keys: makeCacheableSignalKeyStore(state.keys, logger),
      },
      browser: Browsers.ubuntu('Chrome'),
      printQRInTerminal: false,
      logger,
      connectTimeoutMs: 30_000,
      retryRequestDelayMs: 500,
    });

    this.sessions.set(tenantId, { socket: sock, status: 'connecting', qr: null });
    await this.redis.sadd(SESSIONS_KEY, tenantId);

    sock.ev.on('creds.update', saveCreds);

    sock.ev.on('connection.update', async ({ connection, lastDisconnect, qr }) => {
      const current = this.sessions.get(tenantId);
      if (!current) return;

      if (qr) {
        this.log.log(`QR generated for tenant ${tenantId}`);
        this.sessions.set(tenantId, { ...current, qr, status: 'connecting' });
      }

      if (connection === 'open') {
        this.log.log(`Tenant ${tenantId} connected`);
        this.sessions.set(tenantId, { ...current, socket: sock, status: 'open', qr: null });
      }

      if (connection === 'close') {
        const code = (lastDisconnect?.error as Boom)?.output?.statusCode;
        const isLoggedOut = code === DisconnectReason.loggedOut || code === DisconnectReason.forbidden;
        // 405 = HTTP Method Not Allowed (versão/protocolo rejeitado), 515 = restartRequired
        const isRestartRequired = code === DisconnectReason.restartRequired || code === 405;

        this.log.warn(`Tenant ${tenantId} disconnected (code ${code})`);

        if (isLoggedOut) {
          // Conta deslogada — não reconectar
          this.sessions.set(tenantId, { socket: null, status: 'closed', qr: null });
          await this.redis.srem(SESSIONS_KEY, tenantId);
          this.removeAuthFiles(tenantId);
        } else if (isRestartRequired) {
          // Credenciais ruins ou protocolo desatualizado — limpar e tentar de novo após 5s
          this.sessions.set(tenantId, { ...current, socket: null, status: 'connecting' });
          this.removeAuthFiles(tenantId);
          setTimeout(() => this.startSession(tenantId), 5_000);
        } else {
          // Erro transitório — reconectar após 3s mantendo QR existente
          this.sessions.set(tenantId, { ...current, socket: null, status: 'connecting' });
          setTimeout(() => this.startSession(tenantId), 3_000);
        }
      }
    });
  }

  private waitFor(
    tenantId: string,
    condition: (s: SessionState) => boolean,
    timeout: number,
  ): Promise<void> {
    return new Promise((resolve) => {
      const start = Date.now();
      const interval = setInterval(() => {
        const state = this.sessions.get(tenantId);
        if ((state && condition(state)) || Date.now() - start >= timeout) {
          clearInterval(interval);
          resolve();
        }
      }, 200);
    });
  }

  private normalizePhone(phone: string): string {
    const digits = phone.replace(/\D/g, '');
    if (digits.length === 10 || digits.length === 11) {
      return '55' + digits;
    }
    return digits;
  }

  private removeAuthFiles(tenantId: string): void {
    const authDir = path.join(AUTH_DIR, tenantId);
    fs.rmSync(authDir, { recursive: true, force: true });
  }
}
