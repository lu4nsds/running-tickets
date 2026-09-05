import type Redis from 'ioredis';
import { redisPrefix } from '@src/config/redis-keys';
import {
  claimOwnership,
  getOwner,
  releaseOwnership,
  renewOwnership,
} from '@src/states/redis-ownership.state';

const ownerKey = (uuid: string): string => `${redisPrefix()}:owner:${uuid}`;

const makeRedis = () =>
  ({
    set: jest.fn(),
    eval: jest.fn(),
    get: jest.fn(),
  }) as unknown as jest.Mocked<Pick<Redis, 'set' | 'eval' | 'get'>>;

describe('redis-ownership.state', () => {
  describe('claimOwnership', () => {
    it('uses SET NX with a PX ttl and returns true when it wins', async () => {
      const redis = makeRedis();
      redis.set.mockResolvedValue('OK');

      const claimed = await claimOwnership(
        redis as unknown as Redis,
        't1',
        'inst-a',
        30000,
      );

      expect(claimed).toBe(true);
      expect(redis.set).toHaveBeenCalledWith(
        ownerKey('t1'),
        'inst-a',
        'PX',
        30000,
        'NX',
      );
    });

    it('returns false when another instance already owns it', async () => {
      const redis = makeRedis();
      redis.set.mockResolvedValue(null);

      const claimed = await claimOwnership(
        redis as unknown as Redis,
        't1',
        'inst-a',
        30000,
      );

      expect(claimed).toBe(false);
    });
  });

  describe('renewOwnership', () => {
    it('returns true only when the compare-and-extend script succeeds', async () => {
      const redis = makeRedis();
      redis.eval.mockResolvedValue(1);

      const held = await renewOwnership(
        redis as unknown as Redis,
        't1',
        'inst-a',
        30000,
      );

      expect(held).toBe(true);
      expect(redis.eval).toHaveBeenCalledWith(
        expect.stringContaining('pexpire'),
        1,
        ownerKey('t1'),
        'inst-a',
        '30000',
      );
    });

    it('returns false when ownership was lost', async () => {
      const redis = makeRedis();
      redis.eval.mockResolvedValue(0);

      const held = await renewOwnership(
        redis as unknown as Redis,
        't1',
        'inst-a',
        30000,
      );

      expect(held).toBe(false);
    });
  });

  describe('releaseOwnership', () => {
    it('runs the compare-and-del script', async () => {
      const redis = makeRedis();
      redis.eval.mockResolvedValue(1);

      await releaseOwnership(redis as unknown as Redis, 't1', 'inst-a');

      expect(redis.eval).toHaveBeenCalledWith(
        expect.stringContaining('del'),
        1,
        ownerKey('t1'),
        'inst-a',
      );
    });
  });

  describe('getOwner', () => {
    it('reads the current owner key', async () => {
      const redis = makeRedis();
      redis.get.mockResolvedValue('inst-b');

      const owner = await getOwner(redis as unknown as Redis, 't1');

      expect(owner).toBe('inst-b');
      expect(redis.get).toHaveBeenCalledWith(ownerKey('t1'));
    });
  });
});
