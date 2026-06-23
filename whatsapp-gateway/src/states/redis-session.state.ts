import type Redis from 'ioredis';
import { redisPrefix } from '@src/config/redis-keys';
import type { GatewayStatus, SessionState } from '@src/types/whatsapp.type';

function stateKey(tenantUuid: string): string {
  return `${redisPrefix()}:state:${tenantUuid}`;
}

const DEFAULT_STATE: SessionState = {
  status: 'closed',
  qr: null,
  reconnectAttempts: 0,
};

// Persist (a subset of) the observable session state. The hash carries the same
// TTL as the ownership lock so that, if the owning instance dies, the state
// expires and any reader sees the tenant as `closed` instead of a stale status.
export async function setSessionState(
  redis: Redis,
  tenantUuid: string,
  patch: Partial<SessionState>,
  ttlMs: number,
): Promise<void> {
  const fields: Record<string, string> = {};
  if (patch.status !== undefined) fields.status = patch.status;
  if (patch.qr !== undefined) fields.qr = patch.qr ?? '';
  if (patch.reconnectAttempts !== undefined) {
    fields.reconnectAttempts = String(patch.reconnectAttempts);
  }

  if (Object.keys(fields).length === 0) return;

  await redis.hset(stateKey(tenantUuid), fields);
  await redis.pexpire(stateKey(tenantUuid), ttlMs);
}

// Read the single source of truth for a tenant's status/qr. Returns the default
// `closed` state when nothing is stored (unknown or expired tenant).
export async function getSessionState(
  redis: Redis,
  tenantUuid: string,
): Promise<SessionState> {
  const raw = await redis.hgetall(stateKey(tenantUuid));

  if (!raw || Object.keys(raw).length === 0) {
    return { ...DEFAULT_STATE };
  }

  return {
    status: (raw.status as GatewayStatus) || DEFAULT_STATE.status,
    qr: raw.qr ? raw.qr : null,
    reconnectAttempts: raw.reconnectAttempts
      ? Number(raw.reconnectAttempts)
      : 0,
  };
}

// Keep the state alive alongside the ownership lock (called by the heartbeat).
export async function refreshSessionStateTtl(
  redis: Redis,
  tenantUuid: string,
  ttlMs: number,
): Promise<void> {
  await redis.pexpire(stateKey(tenantUuid), ttlMs);
}

export async function clearSessionState(
  redis: Redis,
  tenantUuid: string,
): Promise<void> {
  await redis.del(stateKey(tenantUuid));
}
