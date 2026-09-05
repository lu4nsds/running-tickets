import type { proto } from 'baileys';
import type Redis from 'ioredis';
import { assertTenantUuid, redisPrefix } from '@src/config/redis-keys';
import { importBaileys } from '@src/utils/baileys.import';

const MESSAGE_TTL_SECONDS = 60 * 60 * 24 * 7;

function messagePrefix(): string {
  return `${redisPrefix()}:msgs`;
}

function messageKey(tenantUuid: string, remoteJid: string, id: string): string {
  return `${messagePrefix()}:${tenantUuid}:${remoteJid}:${id}`;
}

// Padrão glob — mesma invariante de `authPattern` (ver `assertTenantUuid`).
export function messagesPattern(tenantUuid: string): string {
  return `${messagePrefix()}:${assertTenantUuid(tenantUuid)}:*`;
}

export async function cacheMessage(
  redis: Redis,
  tenantUuid: string,
  remoteJid: string | null | undefined,
  id: string | null | undefined,
  message: proto.IMessage | null | undefined,
): Promise<void> {
  if (!remoteJid || !id || !message) return;

  const { BufferJSON } = await importBaileys();

  await redis.set(
    messageKey(tenantUuid, remoteJid, id),
    JSON.stringify(message, BufferJSON.replacer),
    'EX',
    MESSAGE_TTL_SECONDS,
  );
}

// A cache miss must yield `undefined` (Baileys then skips the retry) — never a
// placeholder message: replying to a decryption retry with an empty payload
// leaves the recipient stuck on "Waiting for this message".
export async function loadCachedMessage(
  redis: Redis,
  tenantUuid: string,
  remoteJid: string | null | undefined,
  id: string | null | undefined,
): Promise<proto.IMessage | undefined> {
  if (remoteJid && id) {
    const raw = await redis.get(messageKey(tenantUuid, remoteJid, id));
    if (raw) {
      const { BufferJSON } = await importBaileys();
      return JSON.parse(raw, BufferJSON.reviver) as proto.IMessage;
    }
  }

  return undefined;
}
