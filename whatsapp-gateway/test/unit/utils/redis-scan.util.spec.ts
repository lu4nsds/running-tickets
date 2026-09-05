import { Readable } from 'node:stream';
import type Redis from 'ioredis';
import { scanDelete } from '@src/utils/redis-scan.util';

interface FakePipeline {
  unlink: jest.Mock;
  exec: jest.Mock;
}

function buildRedis(batches: string[][]): {
  redis: Redis;
  scanStream: jest.Mock;
  pipelines: FakePipeline[];
  unlinked: string[];
} {
  const pipelines: FakePipeline[] = [];
  const unlinked: string[] = [];

  const pipeline = (): FakePipeline => {
    const keys: string[] = [];
    const fake: FakePipeline = {
      unlink: jest.fn((key: string) => {
        keys.push(key);
        unlinked.push(key);
        return fake;
      }),
      exec: jest.fn(() =>
        Promise.resolve(keys.map(() => [null, 1] as [null, number])),
      ),
    };
    pipelines.push(fake);
    return fake;
  };

  const scanStream = jest.fn(() => Readable.from(batches));

  return {
    redis: {
      scanStream,
      pipeline: jest.fn(pipeline),
    } as unknown as Redis,
    scanStream,
    pipelines,
    unlinked,
  };
}

describe('scanDelete', () => {
  it('scans with the given pattern in bounded batches', async () => {
    const { redis, scanStream } = buildRedis([['a']]);

    await scanDelete(redis, 'test:auth:t1:*');

    expect(scanStream).toHaveBeenCalledWith({
      match: 'test:auth:t1:*',
      count: 200,
    });
  });

  it('unlinks every key returned across batches and totals the removals', async () => {
    const { redis, unlinked } = buildRedis([['k1', 'k2'], ['k3']]);

    const deleted = await scanDelete(redis, 'test:*');

    expect(unlinked).toEqual(['k1', 'k2', 'k3']);
    expect(deleted).toBe(3);
  });

  it('pipelines each batch instead of issuing one round trip per key', async () => {
    const { redis, pipelines } = buildRedis([['k1', 'k2'], ['k3']]);

    await scanDelete(redis, 'test:*');

    expect(pipelines).toHaveLength(2);
    expect(pipelines[0].unlink).toHaveBeenCalledTimes(2);
  });

  it('skips empty batches without touching the pipeline', async () => {
    const { redis, pipelines } = buildRedis([[], []]);

    const deleted = await scanDelete(redis, 'test:*');

    expect(pipelines).toHaveLength(0);
    expect(deleted).toBe(0);
  });

  // Nunca usar KEYS: é O(N) sobre o keyspace inteiro e bloqueia um Redis que este
  // serviço divide com o cache, as filas e as sessões do Laravel.
  it('never falls back to the blocking KEYS command', async () => {
    const { redis } = buildRedis([['k1']]);
    const keys = jest.fn();
    (redis as unknown as { keys: jest.Mock }).keys = keys;

    await scanDelete(redis, 'test:*');

    expect(keys).not.toHaveBeenCalled();
  });
});
