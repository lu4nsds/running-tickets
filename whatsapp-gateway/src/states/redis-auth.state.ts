import {
  AuthenticationCreds,
  AuthenticationState,
  BufferJSON,
  SignalDataTypeMap,
  initAuthCreds,
  proto,
} from '@whiskeysockets/baileys';
import type Redis from 'ioredis';
import { redisPrefix } from '@src/config/redis-keys';

function authPrefix(): string {
  return `${redisPrefix()}:auth`;
}

function credKey(tenantUuid: string): string {
  return `${authPrefix()}:${tenantUuid}:creds`;
}

function signalKey(tenantUuid: string, type: string, id: string): string {
  return `${authPrefix()}:${tenantUuid}:${type}-${id}`;
}

export function authPattern(tenantUuid: string): string {
  return `${authPrefix()}:${tenantUuid}:*`;
}

export async function useRedisAuthState(
  redis: Redis,
  tenantUuid: string,
): Promise<{ state: AuthenticationState; saveCreds: () => Promise<void> }> {
  const raw = await redis.get(credKey(tenantUuid));
  const creds: AuthenticationCreds = raw
    ? (JSON.parse(raw, BufferJSON.reviver) as AuthenticationCreds)
    : initAuthCreds();

  const state: AuthenticationState = {
    creds,
    keys: {
      async get<T extends keyof SignalDataTypeMap>(
        type: T,
        ids: string[],
      ): Promise<{ [id: string]: SignalDataTypeMap[T] }> {
        const keys = ids.map((id) => signalKey(tenantUuid, type, id));
        const values = await redis.mget(...keys);

        const result: { [id: string]: SignalDataTypeMap[T] } = {};
        for (let i = 0; i < ids.length; i++) {
          const val = values[i];
          if (!val) continue;

          let parsed = JSON.parse(
            val,
            BufferJSON.reviver,
          ) as SignalDataTypeMap[T];

          if (type === 'app-state-sync-key' && parsed) {
            parsed = proto.Message.AppStateSyncKeyData.fromObject(
              parsed as object,
            ) as unknown as SignalDataTypeMap[T];
          }

          result[ids[i]] = parsed;
        }
        return result;
      },

      async set(data: {
        [T in keyof SignalDataTypeMap]?: {
          [id: string]: SignalDataTypeMap[T] | null;
        };
      }): Promise<void> {
        const pipeline = redis.pipeline();

        for (const [type, entries] of Object.entries(data)) {
          for (const [id, value] of Object.entries(entries ?? {})) {
            const key = signalKey(tenantUuid, type, id);
            if (value === null || value === undefined) {
              pipeline.del(key);
            } else {
              pipeline.set(key, JSON.stringify(value, BufferJSON.replacer));
            }
          }
        }

        await pipeline.exec();
      },
    },
  };

  const saveCreds = async (): Promise<void> => {
    await redis.set(
      credKey(tenantUuid),
      JSON.stringify(state.creds, BufferJSON.replacer),
    );
  };

  return { state, saveCreds };
}
