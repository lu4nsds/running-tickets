import type Redis from 'ioredis';
import { redisPrefix } from '@src/config/redis-keys';

export function ownerKey(tenantUuid: string): string {
  return `${redisPrefix()}:owner:${tenantUuid}`;
}

// Extend the TTL only while we still hold the lock. Prevents a stale heartbeat
// from resurrecting ownership we have already lost to another instance.
const RENEW_SCRIPT = `
if redis.call('get', KEYS[1]) == ARGV[1] then
  return redis.call('pexpire', KEYS[1], ARGV[2])
end
return 0
`;

// Release the lock only if it is still ours, so we never delete a key another
// instance has since claimed.
const RELEASE_SCRIPT = `
if redis.call('get', KEYS[1]) == ARGV[1] then
  return redis.call('del', KEYS[1])
end
return 0
`;

// Atomically try to become the owner of a tenant. Returns true only for the
// single instance that wins the race (SET NX). Already-owned tenants return
// false without disturbing the current owner.
export async function claimOwnership(
  redis: Redis,
  tenantUuid: string,
  instanceId: string,
  ttlMs: number,
): Promise<boolean> {
  const result = await redis.set(
    ownerKey(tenantUuid),
    instanceId,
    'PX',
    ttlMs,
    'NX',
  );
  return result === 'OK';
}

// Refresh the lock TTL. Returns false if we no longer own it (someone took over
// or it expired), signalling the caller to tear down its local socket.
export async function renewOwnership(
  redis: Redis,
  tenantUuid: string,
  instanceId: string,
  ttlMs: number,
): Promise<boolean> {
  const result = (await redis.eval(
    RENEW_SCRIPT,
    1,
    ownerKey(tenantUuid),
    instanceId,
    String(ttlMs),
  )) as number;
  return result === 1;
}

export async function releaseOwnership(
  redis: Redis,
  tenantUuid: string,
  instanceId: string,
): Promise<void> {
  await redis.eval(RELEASE_SCRIPT, 1, ownerKey(tenantUuid), instanceId);
}

// The instanceId currently holding the tenant, or null if unowned.
export async function getOwner(
  redis: Redis,
  tenantUuid: string,
): Promise<string | null> {
  return redis.get(ownerKey(tenantUuid));
}
