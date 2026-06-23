import { BufferJSON, proto } from '@whiskeysockets/baileys';
import type Redis from 'ioredis';
import { redisPrefix } from '@src/config/redis-keys';

const MESSAGE_TTL_SECONDS = 60 * 60 * 24 * 7;

function messagePrefix(): string {
  return `${redisPrefix()}:msgs`;
}

function messageKey(tenantUuid: string, remoteJid: string, id: string): string {
  return `${messagePrefix()}:${tenantUuid}:${remoteJid}:${id}`;
}

export function messagesPattern(tenantUuid: string): string {
  return `${messagePrefix()}:${tenantUuid}:*`;
}

export async function cacheMessage(
  redis: Redis,
  tenantUuid: string,
  remoteJid: string | null | undefined,
  id: string | null | undefined,
  message: proto.IMessage | null | undefined,
): Promise<void> {
  if (!remoteJid || !id || !message) return;

  await redis.set(
    messageKey(tenantUuid, remoteJid, id),
    JSON.stringify(message, BufferJSON.replacer),
    'EX',
    MESSAGE_TTL_SECONDS,
  );
}

export async function loadCachedMessage(
  redis: Redis,
  tenantUuid: string,
  remoteJid: string | null | undefined,
  id: string | null | undefined,
): Promise<proto.IMessage> {
  if (remoteJid && id) {
    const raw = await redis.get(messageKey(tenantUuid, remoteJid, id));
    if (raw) {
      return JSON.parse(raw, BufferJSON.reviver) as proto.IMessage;
    }
  }

  return proto.Message.fromObject({ conversation: '' });
}
