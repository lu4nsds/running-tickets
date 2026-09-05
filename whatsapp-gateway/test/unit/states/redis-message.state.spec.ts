// Baileys v7 é ESM-only e carregado via o helper `importBaileys`; o Jest não
// consegue parsear o pacote, então mockamos o helper com stand-ins transparentes
// a JSON dos símbolos que o message state usa.
jest.mock('@src/utils/baileys.import', () => ({
  importBaileys: jest.fn().mockResolvedValue({
    BufferJSON: {
      replacer: (_key: string, value: unknown): unknown => value,
      reviver: (_key: string, value: unknown): unknown => value,
    },
    proto: {},
  }),
}));

import type Redis from 'ioredis';
import { redisPrefix } from '@src/config/redis-keys';
import {
  cacheMessage,
  loadCachedMessage,
  messagesPattern,
} from '@src/states/redis-message.state';
import { importBaileys } from '@src/utils/baileys.import';

let BufferJSON: { replacer: (k: string, v: unknown) => unknown };

beforeAll(async () => {
  ({ BufferJSON } = await importBaileys());
});

const TENANT = '3f2504e0-4f89-41d3-9a0c-0305e82c3301';
const messageKey = (jid: string, id: string): string =>
  `${redisPrefix()}:msgs:${TENANT}:${jid}:${id}`;

const makeRedis = () =>
  ({
    get: jest.fn(),
    set: jest.fn(),
  }) as unknown as jest.Mocked<Pick<Redis, 'get' | 'set'>>;

describe('redis-message.state', () => {
  describe('messagesPattern', () => {
    it('matches every cached message of the tenant', () => {
      expect(messagesPattern(TENANT)).toBe(`${redisPrefix()}:msgs:${TENANT}:*`);
    });

    it('refuses to build a pattern from a tenant id carrying a glob', () => {
      expect(() => messagesPattern('*')).toThrow('Invalid tenant uuid');
    });
  });

  describe('cacheMessage', () => {
    it('stores the message under the tenant/jid/id key with a TTL', async () => {
      const redis = makeRedis();

      await cacheMessage(redis as unknown as Redis, TENANT, 'jid', 'm1', {
        conversation: 'hello',
      });

      expect(redis.set).toHaveBeenCalledWith(
        messageKey('jid', 'm1'),
        JSON.stringify({ conversation: 'hello' }, BufferJSON.replacer),
        'EX',
        expect.any(Number),
      );
    });

    it('ignores messages missing jid, id or body', async () => {
      const redis = makeRedis();

      await cacheMessage(redis as unknown as Redis, TENANT, null, 'm1', {});
      await cacheMessage(redis as unknown as Redis, TENANT, 'jid', null, {});
      await cacheMessage(redis as unknown as Redis, TENANT, 'jid', 'm1', null);

      expect(redis.set).not.toHaveBeenCalled();
    });
  });

  describe('loadCachedMessage', () => {
    it('revives a cached message', async () => {
      const redis = makeRedis();
      redis.get.mockResolvedValue(
        JSON.stringify({ conversation: 'hello' }, BufferJSON.replacer),
      );

      const message = await loadCachedMessage(
        redis as unknown as Redis,
        TENANT,
        'jid',
        'm1',
      );

      expect(message).toEqual({ conversation: 'hello' });
      expect(redis.get).toHaveBeenCalledWith(messageKey('jid', 'm1'));
    });

    it('returns undefined on a cache miss instead of a placeholder', async () => {
      // An empty placeholder here would answer decryption retries with a blank
      // payload, leaving recipients stuck on "Waiting for this message".
      const redis = makeRedis();
      redis.get.mockResolvedValue(null);

      const message = await loadCachedMessage(
        redis as unknown as Redis,
        TENANT,
        'jid',
        'm1',
      );

      expect(message).toBeUndefined();
    });

    it('returns undefined when the key parts are missing', async () => {
      const redis = makeRedis();

      const message = await loadCachedMessage(
        redis as unknown as Redis,
        TENANT,
        null,
        undefined,
      );

      expect(message).toBeUndefined();
      expect(redis.get).not.toHaveBeenCalled();
    });
  });
});
