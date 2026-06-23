import { redisPrefix, sessionSetKey } from '@src/config/redis-keys';

describe('redis-keys', () => {
  const original = process.env.REDIS_PREFIX;

  afterEach(() => {
    if (original === undefined) {
      delete process.env.REDIS_PREFIX;
    } else {
      process.env.REDIS_PREFIX = original;
    }
  });

  describe('redisPrefix', () => {
    it('returns the configured prefix verbatim (sem separador)', () => {
      process.env.REDIS_PREFIX = 'acme:whatsapp';
      expect(redisPrefix()).toBe('acme:whatsapp');
    });

    it('throws when REDIS_PREFIX is unset', () => {
      delete process.env.REDIS_PREFIX;
      expect(() => redisPrefix()).toThrow(
        'REDIS_PREFIX environment variable is required',
      );
    });

    it('throws when REDIS_PREFIX is empty', () => {
      process.env.REDIS_PREFIX = '';
      expect(() => redisPrefix()).toThrow(
        'REDIS_PREFIX environment variable is required',
      );
    });
  });

  describe('sessionSetKey', () => {
    it('derives the sessions set key from the prefix, adding the separator', () => {
      process.env.REDIS_PREFIX = 'acme:whatsapp';
      expect(sessionSetKey()).toBe('acme:whatsapp:sessions');
    });
  });
});
