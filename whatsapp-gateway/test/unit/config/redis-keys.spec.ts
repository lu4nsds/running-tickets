import {
  assertTenantUuid,
  redisPrefix,
  sessionSetKey,
} from '@src/config/redis-keys';

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

  describe('assertTenantUuid', () => {
    it('returns the uuid unchanged when well-formed', () => {
      const uuid = '3f2504e0-4f89-41d3-9a0c-0305e82c3301';
      expect(assertTenantUuid(uuid)).toBe(uuid);
    });

    it('accepts uppercase uuids', () => {
      const uuid = '3F2504E0-4F89-41D3-9A0C-0305E82C3301';
      expect(assertTenantUuid(uuid)).toBe(uuid);
    });

    // Estes são os valores que transformariam um padrão glob de um tenant num
    // padrão que casa com as chaves de todos eles.
    it.each(['*', '?', '[a-z]', '3f2504e0-4f89-41d3-9a0c-0305e82c33*', ''])(
      'rejects %p',
      (value) => {
        expect(() => assertTenantUuid(value)).toThrow('Invalid tenant uuid');
      },
    );
  });
});
