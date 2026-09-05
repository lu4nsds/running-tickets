// Baileys v7 é ESM-only e carregado via o helper `importBaileys`; o Jest não
// consegue parsear o pacote, então mockamos o helper com stand-ins transparentes
// a JSON dos poucos símbolos que o auth state usa.
jest.mock('@src/utils/baileys.import', () => ({
  importBaileys: jest.fn().mockResolvedValue({
    BufferJSON: {
      replacer: (_key: string, value: unknown): unknown => value,
      reviver: (_key: string, value: unknown): unknown => value,
    },
    initAuthCreds: jest.fn(() => ({ registrationId: 123 })),
    proto: {
      Message: { AppStateSyncKeyData: { create: (obj: object) => obj } },
    },
  }),
}));

import type Redis from 'ioredis';
import { redisPrefix } from '@src/config/redis-keys';
import { authPattern, useRedisAuthState } from '@src/states/redis-auth.state';

const TENANT = '3f2504e0-4f89-41d3-9a0c-0305e82c3301';
const INSTANCE = 'inst-self';
const ownerKey = `${redisPrefix()}:owner:${TENANT}`;
const credKey = `${redisPrefix()}:auth:${TENANT}:creds`;
const signalKey = (type: string, id: string): string =>
  `${redisPrefix()}:auth:${TENANT}:${type}-${id}`;

// In-memory fake that executes the fenced-write Lua script's semantics in JS:
// writes land only while `owners` still maps the lock to the caller's id.
class FakeRedis {
  values = new Map<string, string>();
  owners = new Map<string, string>([[ownerKey, INSTANCE]]);

  get = jest.fn(
    (key: string): Promise<string | null> =>
      Promise.resolve(this.values.get(key) ?? null),
  );

  mget = jest.fn(
    (...keys: string[]): Promise<Array<string | null>> =>
      Promise.resolve(keys.map((key) => this.values.get(key) ?? null)),
  );

  eval = jest.fn(
    (_script: string, numKeys: number, ...rest: string[]): Promise<number> => {
      const keys = rest.slice(0, numKeys);
      const args = rest.slice(numKeys);

      if (this.owners.get(keys[0]) !== args[0]) return Promise.resolve(0);

      for (let i = 1; i < keys.length; i++) {
        const op = args[i];
        if (op.startsWith('1')) {
          this.values.set(keys[i], op.slice(1));
        } else {
          this.values.delete(keys[i]);
        }
      }
      return Promise.resolve(1);
    },
  );
}

const build = (redis: FakeRedis) =>
  useRedisAuthState(redis as unknown as Redis, TENANT, INSTANCE);

describe('redis-auth.state', () => {
  afterEach(() => {
    jest.clearAllMocks();
  });

  describe('authPattern', () => {
    it('matches every auth key of the tenant', () => {
      expect(authPattern(TENANT)).toBe(`${redisPrefix()}:auth:${TENANT}:*`);
    });

    // Sem isto, o padrão casaria com as credenciais de todos os tenants e a
    // limpeza de uma sessão apagaria o pareamento de todo mundo.
    it('refuses to build a pattern from a tenant id carrying a glob', () => {
      expect(() => authPattern('*')).toThrow('Invalid tenant uuid');
    });
  });

  describe('creds bootstrap', () => {
    it('initialises fresh creds when none are persisted', async () => {
      const redis = new FakeRedis();

      const { state } = await build(redis);

      expect(state.creds.registrationId).toBeDefined();
    });

    it('revives persisted creds from Redis', async () => {
      const redis = new FakeRedis();
      redis.values.set(credKey, JSON.stringify({ registrationId: 999 }));

      const { state } = await build(redis);

      expect(state.creds.registrationId).toBe(999);
    });
  });

  describe('saveCreds (fenced)', () => {
    it('persists creds while this instance still owns the tenant', async () => {
      const redis = new FakeRedis();
      const { saveCreds } = await build(redis);

      await saveCreds();

      expect(redis.values.has(credKey)).toBe(true);
    });

    it('skips the write when ownership moved to another instance', async () => {
      const redis = new FakeRedis();
      const { saveCreds } = await build(redis);
      redis.owners.set(ownerKey, 'inst-other');

      await saveCreds();

      expect(redis.values.has(credKey)).toBe(false);
    });
  });

  describe('keys.set (fenced)', () => {
    it('writes and deletes signal keys atomically while owned', async () => {
      const redis = new FakeRedis();
      redis.values.set(signalKey('session', 'old'), '"stale"');
      const { state } = await build(redis);

      await state.keys.set({
        session: { new: 'payload' as never, old: null },
      });

      expect(redis.values.get(signalKey('session', 'new'))).toBe('"payload"');
      expect(redis.values.has(signalKey('session', 'old'))).toBe(false);
      // single atomic script call: guard + writes together (no TOCTOU)
      expect(redis.eval).toHaveBeenCalledTimes(1);
    });

    it('writes nothing when ownership was lost, without throwing', async () => {
      const redis = new FakeRedis();
      const { state } = await build(redis);
      redis.owners.set(ownerKey, 'inst-other');

      await expect(
        state.keys.set({ session: { s1: 'payload' as never } }),
      ).resolves.toBeUndefined();

      expect(redis.values.has(signalKey('session', 's1'))).toBe(false);
    });

    it('does not hit Redis for an empty write set', async () => {
      const redis = new FakeRedis();
      const { state } = await build(redis);
      redis.eval.mockClear();

      await state.keys.set({});

      expect(redis.eval).not.toHaveBeenCalled();
    });
  });

  describe('keys.get', () => {
    it('revives only the ids present in Redis', async () => {
      const redis = new FakeRedis();
      redis.values.set(signalKey('session', 's1'), '"payload"');
      const { state } = await build(redis);

      const result = await state.keys.get('session', ['s1', 'missing']);

      expect(result).toEqual({ s1: 'payload' });
    });
  });
});
