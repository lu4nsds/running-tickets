import { ConfigService } from '@nestjs/config';
import Redis from 'ioredis';

export const REDIS_CLIENT = 'REDIS_CLIENT';
export const REDIS_SUBSCRIBER = 'REDIS_SUBSCRIBER';

export const redisProvider = {
  provide: REDIS_CLIENT,
  inject: [ConfigService],
  useFactory: (config: ConfigService): Redis =>
    new Redis({
      host: config.get<string>('REDIS_HOST', 'redis'),
      port: config.get<number>('REDIS_PORT', 6379),
      password: config.get<string>('REDIS_PASSWORD') || undefined,
    }),
};

// A dedicated connection for pub/sub: ioredis in subscriber mode cannot issue
// regular commands, so the session bus needs its own client separate from the
// one used for ownership/state reads and writes.
export const redisSubscriberProvider = {
  provide: REDIS_SUBSCRIBER,
  inject: [REDIS_CLIENT],
  useFactory: (redis: Redis): Redis => redis.duplicate(),
};
