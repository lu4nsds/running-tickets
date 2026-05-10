import { BufferJSON, proto } from '@whiskeysockets/baileys';
import type Redis from 'ioredis';

const MESSAGE_KEY_PREFIX = 'neobarber:whatsapp:msgs';
const MESSAGE_TTL_SECONDS = 60 * 60 * 24 * 7;

function messageKey(tenantUuid: string, remoteJid: string, id: string): string {
  return `${MESSAGE_KEY_PREFIX}:${tenantUuid}:${remoteJid}:${id}`;
}

export function messagesPattern(tenantUuid: string): string {
  return `${MESSAGE_KEY_PREFIX}:${tenantUuid}:*`;
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
