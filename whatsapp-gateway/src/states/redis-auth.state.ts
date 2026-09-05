import { Logger } from '@nestjs/common';
import type {
  AuthenticationCreds,
  AuthenticationState,
  SignalDataTypeMap,
} from 'baileys';
import type Redis from 'ioredis';
import { assertTenantUuid, redisPrefix } from '@src/config/redis-keys';
import { ownerKey } from '@src/states/redis-ownership.state';
import { importBaileys } from '@src/utils/baileys.import';

function authPrefix(): string {
  return `${redisPrefix()}:auth`;
}

function credKey(tenantUuid: string): string {
  return `${authPrefix()}:${tenantUuid}:creds`;
}

function signalKey(tenantUuid: string, type: string, id: string): string {
  return `${authPrefix()}:${tenantUuid}:${type}-${id}`;
}

// Padrão glob: o uuid é validado para não vazar metacaracteres que fariam o
// padrão casar com as chaves de outros tenants (ver `assertTenantUuid`).
export function authPattern(tenantUuid: string): string {
  return `${authPrefix()}:${assertTenantUuid(tenantUuid)}:*`;
}

// Fenced write: only the instance that still holds the tenant's ownership lock
// may persist auth state. The check and the writes run atomically in a single
// Lua script (no TOCTOU), so a zombie socket on an instance that lost the lock
// can never corrupt the Signal ratchet another instance is advancing.
//
// KEYS[1] = owner lock key; KEYS[2..n] = auth keys to write.
// ARGV[1] = expected instanceId; ARGV[2..n] = per-key op aligned with KEYS:
// '1' + payload → SET payload, '0' → DEL.
const FENCED_WRITE_SCRIPT = `
if redis.call('get', KEYS[1]) ~= ARGV[1] then
  return 0
end
for i = 2, #KEYS do
  local op = ARGV[i]
  if string.sub(op, 1, 1) == '1' then
    redis.call('set', KEYS[i], string.sub(op, 2))
  else
    redis.call('del', KEYS[i])
  end
end
return 1
`;

async function fencedWrite(
  redis: Redis,
  tenantUuid: string,
  instanceId: string,
  entries: Array<{ key: string; value: string | null }>,
): Promise<boolean> {
  if (entries.length === 0) return true;

  const keys = [ownerKey(tenantUuid), ...entries.map((entry) => entry.key)];
  const args = [
    instanceId,
    ...entries.map((entry) => (entry.value === null ? '0' : `1${entry.value}`)),
  ];

  const result = (await redis.eval(
    FENCED_WRITE_SCRIPT,
    keys.length,
    ...keys,
    ...args,
  )) as number;

  return result === 1;
}

export async function useRedisAuthState(
  redis: Redis,
  tenantUuid: string,
  instanceId: string,
): Promise<{ state: AuthenticationState; saveCreds: () => Promise<void> }> {
  const logger = new Logger(`RedisAuthState:${tenantUuid}`);

  const { BufferJSON, initAuthCreds, proto } = await importBaileys();

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
            // Baileys v7 enxugou os protobufs: restam apenas create/encode/decode.
            // `create` substitui o antigo `fromObject` para reidratar a chave.
            parsed = proto.Message.AppStateSyncKeyData.create(
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
        const entries: Array<{ key: string; value: string | null }> = [];

        for (const [type, typeEntries] of Object.entries(data)) {
          for (const [id, value] of Object.entries(typeEntries ?? {})) {
            entries.push({
              key: signalKey(tenantUuid, type, id),
              value:
                value === null || value === undefined
                  ? null
                  : JSON.stringify(value, BufferJSON.replacer),
            });
          }
        }

        const written = await fencedWrite(
          redis,
          tenantUuid,
          instanceId,
          entries,
        );
        if (!written) {
          logger.warn(
            `Skipped signal key write: ownership no longer held by ${instanceId}`,
          );
        }
      },
    },
  };

  const saveCreds = async (): Promise<void> => {
    const written = await fencedWrite(redis, tenantUuid, instanceId, [
      {
        key: credKey(tenantUuid),
        value: JSON.stringify(state.creds, BufferJSON.replacer),
      },
    ]);
    if (!written) {
      logger.warn(
        `Skipped creds write: ownership no longer held by ${instanceId}`,
      );
    }
  };

  return { state, saveCreds };
}
