import {
  Inject,
  Injectable,
  Logger,
  OnModuleDestroy,
  OnModuleInit,
} from '@nestjs/common';
import { ModuleRef } from '@nestjs/core';
import type Redis from 'ioredis';
import { GatewayConfig } from '@src/config/gateway.config';
import { sessionSetKey } from '@src/config/redis-keys';
import { INSTANCE_ID } from '@src/providers/instance.provider';
import { REDIS_CLIENT } from '@src/providers/redis.provider';
import { authPattern } from '@src/states/redis-auth.state';
import { messagesPattern } from '@src/states/redis-message.state';
import {
  claimOwnership,
  getOwner,
  releaseOwnership,
  renewOwnership,
} from '@src/states/redis-ownership.state';
import {
  clearSessionState,
  getSessionState,
  refreshSessionStateTtl,
} from '@src/states/redis-session.state';
import { TenantConnection } from '@src/session/tenant-connection.service';
import type { TenantConnectionHooks } from '@src/session/tenant-connection.service';
import type { GatewayStatus } from '@src/types/whatsapp.type';

// Owns the in-process registry of live tenant connections plus the background
// loops that keep them healthy. It assumes the caller already holds ownership of
// any tenant it is asked to open — cross-instance ownership routing is the
// AppService's responsibility, not this one's.
@Injectable()
export class SessionManager implements OnModuleInit, OnModuleDestroy {
  // Registry of non-serializable runtime handles only — never a source of
  // session state. State lives in Redis (single source of truth).
  private readonly sockets = new Map<string, TenantConnection>();

  private heartbeatTimer: NodeJS.Timeout | null = null;
  private reconcileTimer: NodeJS.Timeout | null = null;

  constructor(
    @Inject(REDIS_CLIENT) private readonly redis: Redis,
    @Inject(INSTANCE_ID) private readonly instanceId: string,
    private readonly config: GatewayConfig,
    private readonly moduleRef: ModuleRef,
  ) {}

  async onModuleInit(): Promise<void> {
    await this.reconcile();
    this.reconcileTimer = setInterval(() => {
      void this.reconcile();
    }, this.config.reconcileIntervalMs);
    this.heartbeatTimer = setInterval(() => {
      void this.heartbeat();
    }, this.config.heartbeatIntervalMs);
  }

  async onModuleDestroy(): Promise<void> {
    if (this.reconcileTimer) clearInterval(this.reconcileTimer);
    if (this.heartbeatTimer) clearInterval(this.heartbeatTimer);

    for (const [tenantUuid, conn] of [...this.sockets]) {
      conn.end();
      await releaseOwnership(this.redis, tenantUuid, this.instanceId);
    }
    this.sockets.clear();
  }

  // Build a fresh connection for a tenant we already own, replacing any existing
  // one in the registry. Returns the resulting (Redis-backed) status.
  async open(
    tenantUuid: string,
  ): Promise<{ status: GatewayStatus; qr: string | null }> {
    this.sockets.get(tenantUuid)?.clearReconnectTimer();

    const hooks: TenantConnectionHooks = {
      isActive: (conn) => this.sockets.get(tenantUuid) === conn,
      reopen: async () => {
        await this.open(tenantUuid);
      },
      deregister: (conn) => {
        if (this.sockets.get(tenantUuid) === conn) {
          this.sockets.delete(tenantUuid);
        }
      },
    };

    const conn = await this.moduleRef.resolve(TenantConnection);
    conn.bind(tenantUuid, hooks);

    await conn.open();
    this.sockets.set(tenantUuid, conn);

    return this.status(tenantUuid);
  }

  getConnection(tenantUuid: string): TenantConnection | undefined {
    return this.sockets.get(tenantUuid);
  }

  has(tenantUuid: string): boolean {
    return this.sockets.has(tenantUuid);
  }

  // Wipe every trace of a tenant we own: live socket, ownership lock, session
  // state and the cached auth/message keys.
  async removeLocal(tenantUuid: string): Promise<void> {
    this.sockets.get(tenantUuid)?.teardown();

    await this.redis.srem(sessionSetKey(), tenantUuid);
    await releaseOwnership(this.redis, tenantUuid, this.instanceId);
    await clearSessionState(this.redis, tenantUuid);

    await this.deleteByPattern(authPattern(tenantUuid));
    await this.deleteByPattern(messagesPattern(tenantUuid));

    new Logger(`SessionManager:${tenantUuid}`).log(`Session removed`);
  }

  async waitUntilOpen(tenantUuid: string, timeoutMs: number): Promise<void> {
    const startedAt = Date.now();

    while (Date.now() - startedAt <= timeoutMs) {
      const { status, reconnectAttempts } = await getSessionState(
        this.redis,
        tenantUuid,
      );

      if (status === 'open') return;

      // 'error' is terminal. A 'closed' with no pending reconnect means the
      // session is gone (logged out / cleared), so stop waiting. But a 'closed'
      // with reconnectAttempts > 0 is just a transient backoff window — keep
      // waiting for the in-flight reconnect to land instead of failing early.
      if (
        status === 'error' ||
        (status === 'closed' && reconnectAttempts === 0)
      ) {
        break;
      }

      await new Promise((resolve) => setTimeout(resolve, 250));
    }
  }

  // Periodically renew the lock for every tenant we hold; tear down any socket
  // whose ownership we have lost. Also keeps the state hash alive while idle.
  private async heartbeat(): Promise<void> {
    for (const [tenantUuid, conn] of [...this.sockets]) {
      const held = await renewOwnership(
        this.redis,
        tenantUuid,
        this.instanceId,
        this.config.ownershipTtlMs,
      );

      if (!held) {
        new Logger(`SessionManager:${tenantUuid}`).warn(
          `Lost ownership; tearing down local socket`,
        );
        conn.teardown();
        continue;
      }

      await refreshSessionStateTtl(
        this.redis,
        tenantUuid,
        this.config.ownershipTtlMs,
      );
    }
  }

  // Claim and (re)connect any persisted session that currently has no live
  // owner. Runs at boot and on an interval, giving automatic failover when an
  // instance dies and its ownership locks expire.
  private async reconcile(): Promise<void> {
    const tenants = await this.redis.smembers(sessionSetKey());

    for (const tenantUuid of tenants) {
      if (this.sockets.has(tenantUuid)) continue;

      const owner = await getOwner(this.redis, tenantUuid);
      if (owner) continue;

      const claimed = await claimOwnership(
        this.redis,
        tenantUuid,
        this.instanceId,
        this.config.ownershipTtlMs,
      );
      if (!claimed) continue;

      await this.open(tenantUuid).catch((error: unknown) => {
        const message = error instanceof Error ? error.message : String(error);
        new Logger(`SessionManager:${tenantUuid}`).error(
          `Reconcile connect failed: ${message}`,
        );
      });
    }
  }

  private async status(
    tenantUuid: string,
  ): Promise<{ status: GatewayStatus; qr: string | null }> {
    const state = await getSessionState(this.redis, tenantUuid);
    return { status: state.status, qr: state.qr };
  }

  private async deleteByPattern(pattern: string): Promise<void> {
    const keys = await this.redis.keys(pattern);
    if (keys.length > 0) {
      await this.redis.del(...keys);
    }
  }
}
