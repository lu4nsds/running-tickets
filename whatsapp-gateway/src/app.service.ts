import {
  BadRequestException,
  Inject,
  Injectable,
  InternalServerErrorException,
  Logger,
  OnModuleInit,
} from '@nestjs/common';
import { ConfigService } from '@nestjs/config';
import makeWASocket, {
  DisconnectReason,
  fetchLatestBaileysVersion,
} from '@whiskeysockets/baileys';
import type Redis from 'ioredis';
import { BaileysLoggerAdapter } from '@src/adapters/baileys-logger.adapter';
import { REDIS_CLIENT } from '@src/providers/redis.provider';
import {
  SESSION_KEY_PREFIX,
  authPattern,
  useRedisAuthState,
} from '@src/states/redis-auth.state';
import {
  cacheMessage,
  loadCachedMessage,
  messagesPattern,
} from '@src/states/redis-message.state';
import type {
  GatewayStatus,
  SendDocumentInput,
  SendMessageInput,
  TenantSession,
} from '@src/types/whatsapp.type';

@Injectable()
export class AppService implements OnModuleInit {
  private readonly sessions = new Map<string, TenantSession>();

  constructor(
    @Inject(REDIS_CLIENT) private readonly redis: Redis,
    private readonly config: ConfigService,
  ) {}

  async onModuleInit(): Promise<void> {
    const uuids = await this.redis.smembers(SESSION_KEY_PREFIX);

    for (const uuid of uuids) {
      await this.connect(uuid).catch((error: unknown) => {
        const message = error instanceof Error ? error.message : String(error);
        new Logger(`${AppService.name}:${uuid}`).error(
          `Auto-connect failed: ${message}`,
        );
      });
    }
  }

  async connect(
    tenantUuid: string,
  ): Promise<{ status: GatewayStatus; qr: string | null }> {
    const existing = this.sessions.get(tenantUuid);

    if (existing?.status === 'open' || existing?.isConnecting) {
      return this.getStatus(tenantUuid);
    }

    const session: TenantSession = {
      socket: null,
      status: 'connecting',
      lastQr: null,
      isConnecting: true,
    };

    this.sessions.set(tenantUuid, session);

    try {
      const [{ state, saveCreds }, { version }] = await Promise.all([
        useRedisAuthState(this.redis, tenantUuid),
        fetchLatestBaileysVersion(),
      ]);

      new Logger(`${AppService.name}:${tenantUuid}`).log(
        `Connecting WhatsApp (WA version: ${version.join('.')})`,
      );

      const logLevel = this.config
        .get<string>('WHATSAPP_BAILEYS_LOG_LEVEL')
        ?.trim();

      session.socket = makeWASocket({
        version,
        auth: state,
        logger: new BaileysLoggerAdapter(
          new Logger(`${AppService.name}:${tenantUuid}`),
          logLevel,
        ).build(),
        connectTimeoutMs: this.connectTimeoutMs(),
        defaultQueryTimeoutMs: this.connectTimeoutMs(),
        markOnlineOnConnect: true,
        browser: ['RunningTickets', 'Chrome', '1.0.0'],
        getMessage: (key) =>
          loadCachedMessage(this.redis, tenantUuid, key.remoteJid, key.id),
      });

      session.socket.ev.on('creds.update', () => void saveCreds());
      session.socket.ev.on('messages.upsert', ({ messages }) => {
        for (const msg of messages) {
          void cacheMessage(
            this.redis,
            tenantUuid,
            msg.key?.remoteJid,
            msg.key?.id,
            msg.message,
          );
        }
      });
      session.socket.ev.on('connection.update', (update) => {
        const { connection, qr, lastDisconnect } = update;

        if (qr) {
          session.lastQr = qr;
          new Logger(`${AppService.name}:${tenantUuid}`).log(
            `QR code generated (len=${qr.length})`,
          );
        }

        if (connection === 'open') {
          session.status = 'open';
          session.lastQr = null;
          new Logger(`${AppService.name}:${tenantUuid}`).log(
            `WhatsApp connected`,
          );
          void this.redis.sadd(SESSION_KEY_PREFIX, tenantUuid);
          return;
        }

        if (connection === 'close') {
          const statusCode = (
            lastDisconnect?.error as
              | { output?: { statusCode?: number } }
              | undefined
          )?.output?.statusCode;

          if (statusCode === DisconnectReason.loggedOut) {
            session.status = 'closed';
            new Logger(`${AppService.name}:${tenantUuid}`).warn(
              `WhatsApp logged out; clearing credentials`,
            );
            void this.clearAuthKeys(tenantUuid);
            return;
          }

          if (statusCode === DisconnectReason.restartRequired) {
            new Logger(`${AppService.name}:${tenantUuid}`).warn(
              `WhatsApp restart required; reconnecting...`,
            );
            this.reconnect(tenantUuid).catch((err: unknown) => {
              const msg = err instanceof Error ? err.message : String(err);
              new Logger(`${AppService.name}:${tenantUuid}`).error(
                `Reconnect failed: ${msg}`,
              );
            });
            return;
          }

          session.status = 'closed';
          new Logger(`${AppService.name}:${tenantUuid}`).warn(
            `WhatsApp disconnected (status=${statusCode ?? 'unknown'})`,
          );
        }
      });

      return this.getStatus(tenantUuid);
    } catch (error) {
      session.status = 'error';
      const message = error instanceof Error ? error.message : String(error);
      throw new InternalServerErrorException(
        `Failed to connect WhatsApp for tenant ${tenantUuid}: ${message}`,
      );
    } finally {
      session.isConnecting = false;
    }
  }

  async sendMessage(
    tenantUuid: string,
    input: SendMessageInput,
  ): Promise<{ ok: true; phone: string }> {
    const phone = this.normalizePhone(input.phone);
    const message = input.message?.trim();

    if (!phone || !message) {
      throw new BadRequestException('phone and message are required');
    }

    const session = this.sessions.get(tenantUuid);

    if (!session || session.status !== 'open' || !session.socket) {
      await this.connect(tenantUuid);
      await this.waitUntilOpen(tenantUuid, this.connectTimeoutMs());
    }

    const active = this.sessions.get(tenantUuid);

    if (!active?.socket || active.status !== 'open') {
      throw new InternalServerErrorException(
        `WhatsApp session for tenant ${tenantUuid} is not connected. Pair with QR and retry.`,
      );
    }

    const onWhatsApp = await active.socket.onWhatsApp(
      `${phone}@s.whatsapp.net`,
    );
    const result = onWhatsApp?.[0];

    if (!result?.exists) {
      throw new BadRequestException(
        `The number ${phone} does not have a WhatsApp account.`,
      );
    }

    const sent = await active.socket.sendMessage(result.jid, {
      text: message,
    });

    await cacheMessage(
      this.redis,
      tenantUuid,
      sent?.key?.remoteJid ?? result.jid,
      sent?.key?.id,
      sent?.message,
    );

    return { ok: true, phone: result.jid };
  }

  async sendDocument(
    tenantUuid: string,
    input: SendDocumentInput,
  ): Promise<{ ok: true; phone: string }> {
    const phone = this.normalizePhone(input.phone);

    if (!phone || !input.data || !input.filename || !input.mimetype) {
      throw new BadRequestException('phone, filename, mimetype and data are required');
    }

    const session = this.sessions.get(tenantUuid);

    if (!session || session.status !== 'open' || !session.socket) {
      await this.connect(tenantUuid);
      await this.waitUntilOpen(tenantUuid, this.connectTimeoutMs());
    }

    const active = this.sessions.get(tenantUuid);

    if (!active?.socket || active.status !== 'open') {
      throw new InternalServerErrorException(
        `WhatsApp session for tenant ${tenantUuid} is not connected. Pair with QR and retry.`,
      );
    }

    const onWhatsApp = await active.socket.onWhatsApp(
      `${phone}@s.whatsapp.net`,
    );
    const result = onWhatsApp?.[0];

    if (!result?.exists) {
      throw new BadRequestException(
        `The number ${phone} does not have a WhatsApp account.`,
      );
    }

    const sent = await active.socket.sendMessage(result.jid, {
      document: Buffer.from(input.data, 'base64'),
      mimetype: input.mimetype,
      fileName: input.filename,
      caption: input.caption ?? '',
    });

    await cacheMessage(
      this.redis,
      tenantUuid,
      sent?.key?.remoteJid ?? result.jid,
      sent?.key?.id,
      sent?.message,
    );

    return { ok: true, phone: result.jid };
  }

  getStatus(tenantUuid: string): { status: GatewayStatus; qr: string | null } {
    const session = this.sessions.get(tenantUuid);
    return {
      status: session?.status ?? 'closed',
      qr: session?.lastQr ?? null,
    };
  }

  async removeSession(tenantUuid: string): Promise<void> {
    const session = this.sessions.get(tenantUuid);

    if (session?.socket) {
      session.socket.end(new Error('session removed'));
    }

    this.sessions.delete(tenantUuid);
    await this.redis.srem(SESSION_KEY_PREFIX, tenantUuid);

    const authKeys = await this.redis.keys(authPattern(tenantUuid));
    if (authKeys.length > 0) {
      await this.redis.del(...authKeys);
    }

    const messageKeys = await this.redis.keys(messagesPattern(tenantUuid));
    if (messageKeys.length > 0) {
      await this.redis.del(...messageKeys);
    }

    new Logger(`${AppService.name}:${tenantUuid}`).log(`Session removed`);
  }

  private async clearAuthKeys(tenantUuid: string): Promise<void> {
    await this.redis.srem(SESSION_KEY_PREFIX, tenantUuid);
    const authKeys = await this.redis.keys(authPattern(tenantUuid));
    if (authKeys.length > 0) {
      await this.redis.del(...authKeys);
    }
  }

  private async reconnect(tenantUuid: string): Promise<void> {
    const session = this.sessions.get(tenantUuid);

    if (session?.socket) {
      session.socket.end(new Error('restart required'));
    }

    this.sessions.delete(tenantUuid);
    await this.connect(tenantUuid);
  }

  private async waitUntilOpen(
    tenantUuid: string,
    timeoutMs: number,
  ): Promise<void> {
    const startedAt = Date.now();

    while (Date.now() - startedAt <= timeoutMs) {
      const s = this.sessions.get(tenantUuid)?.status;

      if (s === 'open') return;
      if (s === 'error' || s === 'closed') break;

      await new Promise((resolve) => setTimeout(resolve, 250));
    }
  }

  private connectTimeoutMs(): number {
    const timeout = Number.parseInt(
      this.config.get<string>('WHATSAPP_CONNECT_TIMEOUT_MS', '30000'),
      10,
    );
    return Number.isNaN(timeout) ? 45000 : timeout;
  }

  private normalizePhone(value: string): string {
    const digits = value.replace(/\D/g, '');

    if (digits.length === 10 || digits.length === 11) {
      return `55${digits}`;
    }

    return digits;
  }
}
