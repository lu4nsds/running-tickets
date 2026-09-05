import {
  BadRequestException,
  Inject,
  Injectable,
  InternalServerErrorException,
  Logger,
  Scope,
} from '@nestjs/common';
import type { AnyMessageContent, WASocket } from 'baileys';
import type Redis from 'ioredis';
import { BaileysLoggerAdapter } from '@src/adapters/baileys-logger.adapter';
import { GatewayConfig } from '@src/config/gateway.config';
import { sessionSetKey } from '@src/config/redis-keys';
import { INSTANCE_ID } from '@src/providers/instance.provider';
import { REDIS_CLIENT } from '@src/providers/redis.provider';
import { authPattern, useRedisAuthState } from '@src/states/redis-auth.state';
import { releaseOwnership } from '@src/states/redis-ownership.state';
import {
  cacheMessage,
  loadCachedMessage,
} from '@src/states/redis-message.state';
import {
  clearSessionState,
  getSessionState,
  setSessionState,
} from '@src/states/redis-session.state';
import { importBaileys } from '@src/utils/baileys.import';
import { fireAndForget } from '@src/utils/fire-and-forget.util';
import { scanDelete } from '@src/utils/redis-scan.util';

// Lets a connection coordinate with its owning SessionManager without holding a
// back-reference to it (no circular import). The connection passes itself so the
// manager can compare identities without the manager needing a forward
// reference to the instance it is about to build:
//   - isActive:   is this still the active connection for the tenant? Used to
//                 ignore events from a socket that has already been replaced.
//   - reopen:     ask the manager to build a fresh connection (reconnect).
//   - deregister: remove this connection from the manager's registry.
// Discriminated payload for `send`: either a plain text body or a base64
// document (e.g. the ticket PDF) with an optional caption.
export type SendContent =
  | { text: string }
  | { data: string; mimetype: string; filename: string; caption?: string };

// O logout é best-effort: se o WhatsApp não responder nesse prazo, a remoção da
// sessão segue mesmo assim.
const LOGOUT_TIMEOUT_MS = 5_000;

export interface TenantConnectionHooks {
  isActive(connection: TenantConnection): boolean;
  reopen(): Promise<void>;
  deregister(connection: TenantConnection): void;
}

// Everything that physically belongs to a single tenant's live WhatsApp socket:
// the Baileys handle, its reconnect timer, the event wiring and the
// reconnect/teardown/terminal-cleanup state machine. All observable session
// state still lives in Redis (the single source of truth); this object only
// holds the non-serializable runtime handle and drives its lifecycle.
//
// Transient-scoped: one live instance exists per tenant, resolved on demand by
// the SessionManager via ModuleRef. Shared dependencies are injected; the
// per-tenant identity (uuid + manager hooks) is supplied through `bind`.
@Injectable({ scope: Scope.TRANSIENT })
export class TenantConnection {
  private socket!: WASocket;
  private reconnectTimer: NodeJS.Timeout | null = null;

  private tenantUuid!: string;
  private hooks!: TenantConnectionHooks;
  private logger!: Logger;

  constructor(
    @Inject(REDIS_CLIENT) private readonly redis: Redis,
    @Inject(INSTANCE_ID) private readonly instanceId: string,
    private readonly config: GatewayConfig,
  ) {}

  // Give this freshly-resolved instance its tenant identity and manager hooks.
  // Must be called once, before `open`.
  bind(tenantUuid: string, hooks: TenantConnectionHooks): this {
    this.tenantUuid = tenantUuid;
    this.hooks = hooks;
    this.logger = new Logger(`TenantConnection:${tenantUuid}`);
    return this;
  }

  // Build the live Baileys socket and wire every event into Redis. The caller
  // must already hold ownership of the tenant.
  async open(): Promise<void> {
    await this.setState({ status: 'connecting', qr: null });

    try {
      const { default: makeWASocket, fetchLatestBaileysVersion } =
        await importBaileys();

      const [{ state, saveCreds }, { version }] = await Promise.all([
        useRedisAuthState(this.redis, this.tenantUuid, this.instanceId),
        fetchLatestBaileysVersion(),
      ]);

      this.logger.log(`Connecting WhatsApp (WA version: ${version.join('.')})`);

      this.socket = makeWASocket({
        version,
        auth: state,
        logger: new BaileysLoggerAdapter(
          this.logger,
          this.config.baileysLogLevel,
        ).build(),
        connectTimeoutMs: this.config.connectTimeoutMs,
        defaultQueryTimeoutMs: this.config.connectTimeoutMs,
        markOnlineOnConnect: true,
        browser: [this.config.deviceName, 'Chrome', '1.0.0'],
        getMessage: (key) =>
          loadCachedMessage(this.redis, this.tenantUuid, key.remoteJid, key.id),
      });

      this.socket.ev.on('creds.update', () => {
        fireAndForget(
          saveCreds(),
          this.logger,
          'Failed to persist credentials',
        );
      });
      this.socket.ev.on('messages.upsert', ({ messages }) => {
        for (const msg of messages) {
          fireAndForget(
            cacheMessage(
              this.redis,
              this.tenantUuid,
              msg.key?.remoteJid,
              msg.key?.id,
              msg.message,
            ),
            this.logger,
            'Failed to cache inbound message',
          );
        }
      });
      this.socket.ev.on('connection.update', (update) =>
        this.handleConnectionUpdate(update),
      );
    } catch (error) {
      await this.setState({ status: 'error' });
      const message = error instanceof Error ? error.message : String(error);
      throw new InternalServerErrorException(
        `Failed to connect WhatsApp for tenant ${this.tenantUuid}: ${message}`,
      );
    }
  }

  // Resolve the recipient JID, send the message (text or base64 document) and
  // cache the outbound message so the `getMessage` hook can replay it. Assumes
  // the socket is open.
  async send(phone: string, content: SendContent): Promise<{ jid: string }> {
    const onWhatsApp = await this.socket.onWhatsApp(`${phone}@s.whatsapp.net`);
    const result = onWhatsApp?.[0];

    if (!result?.exists) {
      throw new BadRequestException(
        `The number ${phone} does not have a WhatsApp account.`,
      );
    }

    const payload: AnyMessageContent =
      'text' in content
        ? { text: content.text }
        : {
            document: Buffer.from(content.data, 'base64'),
            mimetype: content.mimetype,
            fileName: content.filename,
            caption: content.caption ?? '',
          };

    const sent = await this.socket.sendMessage(result.jid, payload);

    await cacheMessage(
      this.redis,
      this.tenantUuid,
      sent?.key?.remoteJid ?? result.jid,
      sent?.key?.id,
      sent?.message,
    );

    return { jid: result.jid };
  }

  // Desvincula o aparelho do lado do WhatsApp antes de derrubar o socket. Sem
  // isto, cada "desconectar" pelo painel deixa um aparelho fantasma na lista de
  // dispositivos vinculados do tenant — e o WhatsApp tem limite de aparelhos, de
  // modo que os fantasmas acabam impedindo um novo pareamento.
  //
  // Best-effort com timeout: a sessão está sendo removida de qualquer forma, e um
  // logout que não responde não pode segurar a limpeza do Redis.
  async logout(): Promise<void> {
    try {
      await Promise.race([
        this.socket.logout(),
        new Promise((_, reject) =>
          setTimeout(
            () => reject(new Error('logout timed out')),
            LOGOUT_TIMEOUT_MS,
          ).unref(),
        ),
      ]);
    } catch (error: unknown) {
      const message = error instanceof Error ? error.message : String(error);
      this.logger.warn(`Logout failed, tearing down anyway: ${message}`);
    }

    this.teardown();
  }

  // Drop the socket and stop the reconnect loop. Guarded so a stale connection
  // that has already been replaced cannot remove the active one.
  teardown(): void {
    if (!this.hooks.isActive(this)) return;
    this.clearReconnectTimer();
    try {
      this.removeListeners();
      void this.socket.end(new Error('teardown'));
    } catch {
      // best-effort
    }
    this.hooks.deregister(this);
  }

  clearReconnectTimer(): void {
    if (this.reconnectTimer) {
      clearTimeout(this.reconnectTimer);
      this.reconnectTimer = null;
    }
  }

  // Best-effort stop used during process shutdown; ownership release and
  // registry cleanup are handled by the SessionManager that owns us.
  end(): void {
    this.clearReconnectTimer();
    try {
      void this.socket.end(new Error('shutting down'));
    } catch {
      // best-effort during shutdown
    }
  }

  // --- event handling -------------------------------------------------------

  private handleConnectionUpdate(update: {
    connection?: string;
    qr?: string;
    lastDisconnect?: { error?: unknown };
  }): void {
    // Ignore events from a socket that is no longer the active one for this
    // tenant (e.g. a late 'close' after a reconnect already replaced it).
    if (!this.hooks.isActive(this)) return;

    const { connection, qr, lastDisconnect } = update;

    if (qr) {
      fireAndForget(this.onQr(qr), this.logger, 'Failed to persist QR code');
    }

    if (connection === 'open') {
      fireAndForget(this.onOpen(), this.logger, 'Failed to handle open');
      return;
    }

    if (connection === 'close') {
      const statusCode = (
        lastDisconnect?.error as
          | { output?: { statusCode?: number } }
          | undefined
      )?.output?.statusCode;
      fireAndForget(
        this.onClose(statusCode),
        this.logger,
        'Failed to handle close',
      );
    }
  }

  private async onQr(qr: string): Promise<void> {
    await this.setState({ qr });
    this.logger.log(`QR code generated (len=${qr.length})`);
  }

  private async onOpen(): Promise<void> {
    this.clearReconnectTimer();
    this.logger.log(`WhatsApp connected`);
    await this.setState({ status: 'open', qr: null, reconnectAttempts: 0 });
    await this.redis.sadd(sessionSetKey(), this.tenantUuid);
  }

  private async onClose(statusCode: number | undefined): Promise<void> {
    const { DisconnectReason } = await importBaileys();

    if (statusCode === DisconnectReason.loggedOut) {
      this.logger.warn(`WhatsApp logged out; clearing credentials`);
      await this.releaseAndCleanup();
      return;
    }

    if (statusCode === DisconnectReason.connectionReplaced) {
      // Another session took over (e.g. WhatsApp Web opened elsewhere).
      // Reconnecting would fight it in a loop, so we stop and keep the
      // credentials so the tenant can reconnect manually later.
      this.logger.warn(
        `WhatsApp connection replaced by another session; not reconnecting`,
      );
      await this.handleReplaced();
      return;
    }

    // All other reasons (badSession=500, restartRequired=515, connectionLost,
    // connectionClosed, timedOut, unavailableService or unknown) are transient
    // and trigger a backoff reconnect.
    await this.scheduleReconnect();
  }

  // --- reconnect / cleanup --------------------------------------------------

  // Uma queda transiente NUNCA apaga credenciais. Antes, `reconnectMaxAttempts`
  // tentativas (≈1 min de backoff) disparavam o wipe e obrigavam o tenant a
  // escanear o QR de novo — qualquer instabilidade do WhatsApp, do Redis ou do
  // container maior que um minuto custava um repareamento manual. E os motivos
  // mais comuns (connectionLost, restartRequired=515, timedOut=408) são rotina.
  //
  // Hoje o limite só marca a entrada em modo degradado: as tentativas continuam
  // para sempre, com o backoff no teto de `reconnectMaxDelayMs`. As credenciais
  // só são apagadas em `loggedOut`, quando o WhatsApp já invalidou a sessão.
  private async scheduleReconnect(): Promise<void> {
    const state = await getSessionState(this.redis, this.tenantUuid);
    const attempts = state.reconnectAttempts + 1;
    const degraded = attempts > this.config.reconnectMaxAttempts;

    await this.setState({ status: 'closed', reconnectAttempts: attempts });

    const delay = degraded
      ? this.config.reconnectMaxDelayMs
      : Math.min(
          this.config.reconnectBaseDelayMs * 2 ** (attempts - 1),
          this.config.reconnectMaxDelayMs,
        );

    if (degraded) {
      this.logger.error(
        `WhatsApp still disconnected after ${this.config.reconnectMaxAttempts} attempts; ` +
          `retrying every ${delay}ms with credentials intact (attempt ${attempts})`,
      );
    } else {
      this.logger.warn(
        `WhatsApp disconnected; reconnect attempt ${attempts} in ${delay}ms`,
      );
    }

    this.reconnectTimer = setTimeout(() => {
      this.reconnect().catch((err: unknown) => {
        const msg = err instanceof Error ? err.message : String(err);
        this.logger.error(`Reconnect failed: ${msg}`);
      });
    }, delay);
  }

  private async reconnect(): Promise<void> {
    // Skip if a newer connection already replaced this one for the tenant.
    if (!this.hooks.isActive(this)) return;

    this.removeListeners();
    void this.socket.end(new Error('reconnecting'));

    // reconnectAttempts persists in Redis; reopen builds a fresh connection.
    // A successful 'open' resets the counter.
    await this.hooks.reopen();
  }

  // Único caminho terminal: o WhatsApp desvinculou o aparelho (`loggedOut`), então
  // as credenciais já não valem mais nada. Derruba o socket, apaga as chaves de
  // auth, solta o lock e limpa o estado — um novo pareamento por QR é inevitável.
  // Nenhuma falha transiente chega aqui (ver `scheduleReconnect`).
  private async releaseAndCleanup(): Promise<void> {
    this.teardown();
    await this.clearAuthKeys();
    await releaseOwnership(this.redis, this.tenantUuid, this.instanceId);
    await clearSessionState(this.redis, this.tenantUuid);
  }

  // connectionReplaced: stop auto-reconnect (remove from the session set and
  // release the lock) but keep credentials for a manual reconnect.
  private async handleReplaced(): Promise<void> {
    this.teardown();
    await this.setState({ status: 'closed' });
    await this.redis.srem(sessionSetKey(), this.tenantUuid);
    await releaseOwnership(this.redis, this.tenantUuid, this.instanceId);
  }

  private async clearAuthKeys(): Promise<void> {
    await this.redis.srem(sessionSetKey(), this.tenantUuid);
    await scanDelete(this.redis, authPattern(this.tenantUuid));
  }

  private removeListeners(): void {
    this.socket.ev.removeAllListeners('connection.update');
    this.socket.ev.removeAllListeners('creds.update');
    this.socket.ev.removeAllListeners('messages.upsert');
  }

  private setState(
    patch: Parameters<typeof setSessionState>[2],
  ): Promise<void> {
    return setSessionState(
      this.redis,
      this.tenantUuid,
      patch,
      this.config.ownershipTtlMs,
    );
  }
}
