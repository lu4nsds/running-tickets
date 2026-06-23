import type Redis from 'ioredis';
import { redisPrefix } from '@src/config/redis-keys';
import {
  clearSessionState,
  getSessionState,
  setSessionState,
} from '@src/states/redis-session.state';

const stateKey = (uuid: string): string => `${redisPrefix()}:state:${uuid}`;

const makeRedis = () =>
  ({
    hset: jest.fn().mockResolvedValue(1),
    hgetall: jest.fn(),
    pexpire: jest.fn().mockResolvedValue(1),
    del: jest.fn().mockResolvedValue(1),
  }) as unknown as jest.Mocked<
    Pick<Redis, 'hset' | 'hgetall' | 'pexpire' | 'del'>
  >;

describe('redis-session.state', () => {
  describe('setSessionState', () => {
    it('writes only provided fields and (re)applies the ttl', async () => {
      const redis = makeRedis();

      await setSessionState(
        redis as unknown as Redis,
        't1',
        { status: 'open', qr: null, reconnectAttempts: 0 },
        30000,
      );

      expect(redis.hset).toHaveBeenCalledWith(stateKey('t1'), {
        status: 'open',
        qr: '',
        reconnectAttempts: '0',
      });
      expect(redis.pexpire).toHaveBeenCalledWith(stateKey('t1'), 30000);
    });

    it('does not touch Redis when the patch is empty', async () => {
      const redis = makeRedis();

      await setSessionState(redis as unknown as Redis, 't1', {}, 30000);

      expect(redis.hset).not.toHaveBeenCalled();
      expect(redis.pexpire).not.toHaveBeenCalled();
    });
  });

  describe('getSessionState', () => {
    it('returns the default closed state when nothing is stored', async () => {
      const redis = makeRedis();
      redis.hgetall.mockResolvedValue({});

      const state = await getSessionState(redis as unknown as Redis, 't1');

      expect(state).toEqual({
        status: 'closed',
        qr: null,
        reconnectAttempts: 0,
      });
    });

    it('parses the stored hash, mapping empty qr to null', async () => {
      const redis = makeRedis();
      redis.hgetall.mockResolvedValue({
        status: 'connecting',
        qr: '',
        reconnectAttempts: '2',
      });

      const state = await getSessionState(redis as unknown as Redis, 't1');

      expect(state).toEqual({
        status: 'connecting',
        qr: null,
        reconnectAttempts: 2,
      });
    });
  });

  describe('clearSessionState', () => {
    it('deletes the state key', async () => {
      const redis = makeRedis();

      await clearSessionState(redis as unknown as Redis, 't1');

      expect(redis.del).toHaveBeenCalledWith(stateKey('t1'));
    });
  });
});
