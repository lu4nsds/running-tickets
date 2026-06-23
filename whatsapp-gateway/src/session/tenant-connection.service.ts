import {
  BadRequestException,
  Inject,
  Injectable,
  InternalServerErrorException,
  Logger,
  Scope,
} from '@nestjs/common';
import makeWASocket, {
  DisconnectReason,
  fetchLatestBaileysVersion,
} from '@whiskeysockets/baileys';
import type { WASocket } from '@whiskeysockets/baileys';
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

// Lets a connection coordinate with its owning SessionManager without holding a
// back-reference to it (no circular import). The connection passes itself so the
// manager can compare identities without the manager needing a forward
// reference to the instance it is about to build:
//   - isActive:   is this still the active connection for the tenant? Used to
//                 ignore events from a socket that has already been replaced.
//   - reopen:     ask the manager to build a fresh connection (reconnect).
//   - deregister: remove this connection from the manager's registry.
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
      const [{ state, saveCreds }, { version }] = await Promise.all([
        useRedisAuthState(this.redis, this.tenantUuid),
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

      this.socket.ev.on('creds.update', () => void saveCreds());
      this.socket.ev.on('messages.upsert', ({ messages }) => {
        for (const msg of messages) {
          void cacheMessage(
            this.redis,
            this.tenantUuid,
            msg.key?.remoteJid,
            msg.key?.id,
            msg.message,
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

  // Resolve the recipient JID, send the text and cache the outbound message so
  // the `getMessage` hook can replay it. Assumes the socket is open.
  async send(phone: string, message: string): Promise<{ jid: string }> {
    const onWhatsApp = await this.socket.onWhatsApp(`${phone}@s.whatsapp.net`);
    const result = onWhatsApp?.[0];

    if (!result?.exists) {
      throw new BadRequestException(
        `The number ${phone} does not have a WhatsApp account.`,
      );
    }

    const sent = await this.socket.sendMessage(result.jid, { text: message });

    await cacheMessage(
      this.redis,
      this.tenantUuid,
      sent?.key?.remoteJid ?? result.jid,
      sent?.key?.id,
      sent?.message,
    );

    return { jid: result.jid };
  }

  // Drop the socket and stop the reconnect loop. Guarded so a stale connection
  // that has already been replaced cannot remove the active one.
  teardown(): void {
    if (!this.hooks.isActive(this)) return;
    this.clearReconnectTimer();
    try {
      this.removeListeners();
      this.socket.end(new Error('teardown'));
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
      this.socket.end(new Error('shutting down'));
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

    if (qr) void this.onQr(qr);

    if (connection === 'open') {
      void this.onOpen();
      return;
    }

    if (connection === 'close') {
      const statusCode = (
        lastDisconnect?.error as
          | { output?: { statusCode?: number } }
          | undefined
      )?.output?.statusCode;
      void this.onClose(statusCode);
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
    if (statusCode === DisconnectReason.loggedOut) {
      this.logger.warn(`WhatsApp logged out; clearing credentials`);
      await this.releaseAndCleanup(true);
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

  private async scheduleReconnect(): Promise<void> {
    const state = await getSessionState(this.redis, this.tenantUuid);
    const attempts = state.reconnectAttempts + 1;

    if (attempts > this.config.reconnectMaxAttempts) {
      this.logger.error(
        `Max reconnect attempts (${this.config.reconnectMaxAttempts}) reached; clearing credentials, re-pair required`,
      );
      await this.releaseAndCleanup(true);
      return;
    }

    await this.setState({ status: 'closed', reconnectAttempts: attempts });

    const delay = Math.min(
      this.config.reconnectBaseDelayMs * 2 ** (attempts - 1),
      this.config.reconnectMaxDelayMs,
    );

    this.logger.warn(
      `WhatsApp disconnected; reconnect attempt ${attempts} in ${delay}ms`,
    );

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
    this.socket.end(new Error('reconnecting'));

    // reconnectAttempts persists in Redis; reopen builds a fresh connection.
    // A successful 'open' resets the counter.
    await this.hooks.reopen();
  }

  // Terminal failure (loggedOut / max attempts): drop the socket, release the
  // lock and clear state. Optionally wipe credentials so a re-pair is required.
  private async releaseAndCleanup(clearCreds: boolean): Promise<void> {
    this.teardown();
    if (clearCreds) await this.clearAuthKeys();
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
    const authKeys = await this.redis.keys(authPattern(this.tenantUuid));
    if (authKeys.length > 0) {
      await this.redis.del(...authKeys);
    }
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
