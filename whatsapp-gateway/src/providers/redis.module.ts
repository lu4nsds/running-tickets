import { Global, Module } from '@nestjs/common';
import {
  RedisLifecycle,
  REDIS_CLIENT,
  REDIS_SUBSCRIBER,
  redisProvider,
  redisSubscriberProvider,
} from '@src/providers/redis.provider';

// Global para que qualquer módulo (o de health, por exemplo) reaproveite as MESMAS
// duas conexões. Provider duplicado em outro módulo abriria uma conexão extra por
// módulo — e, no caso do subscriber, um segundo inscrito no inbox RPC desta
// instância, que processaria cada requisição duas vezes.
@Global()
@Module({
  providers: [redisProvider, redisSubscriberProvider, RedisLifecycle],
  exports: [REDIS_CLIENT, REDIS_SUBSCRIBER],
})
export class RedisModule {}
