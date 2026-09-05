import {
  Inject,
  Injectable,
  Logger,
  OnApplicationShutdown,
} from '@nestjs/common';
import { ConfigService } from '@nestjs/config';
import Redis from 'ioredis';

export const REDIS_CLIENT = 'REDIS_CLIENT';
export const REDIS_SUBSCRIBER = 'REDIS_SUBSCRIBER';

// Backoff limitado: sem teto, uma indisponibilidade longa do Redis empurraria a
// próxima tentativa para minutos e o gateway demoraria a se recuperar mesmo
// depois de o Redis voltar.
const MAX_RETRY_DELAY_MS = 5_000;

function retryStrategy(times: number): number {
  return Math.min(times * 200, MAX_RETRY_DELAY_MS);
}

// Sem um listener de 'error' o ioredis não derruba o processo, mas imprime
// `[ioredis] Unhandled error event` direto no stderr — fora do logger do Nest e,
// portanto, fora de qualquer coleta de log. Anexar o listener traz as falhas de
// conexão para o mesmo canal do resto do serviço.
function attachLogging(client: Redis, name: string): Redis {
  const logger = new Logger(name);

  client.on('error', (error: Error) => {
    logger.error(`Redis connection error: ${error.message}`);
  });
  client.on('reconnecting', () => {
    logger.warn('Reconnecting to Redis');
  });
  client.on('ready', () => {
    logger.log('Redis connection ready');
  });

  return client;
}

export const redisProvider = {
  provide: REDIS_CLIENT,
  inject: [ConfigService],
  useFactory: (config: ConfigService): Redis =>
    attachLogging(
      new Redis({
        host: config.get<string>('REDIS_HOST', 'redis'),
        port: config.get<number>('REDIS_PORT', 6379),
        password: config.get<string>('REDIS_PASSWORD') || undefined,
        retryStrategy,
      }),
      'Redis',
    ),
};

// A dedicated connection for pub/sub: ioredis in subscriber mode cannot issue
// regular commands, so the session bus needs its own client separate from the
// one used for ownership/state reads and writes.
export const redisSubscriberProvider = {
  provide: REDIS_SUBSCRIBER,
  inject: [REDIS_CLIENT],
  useFactory: (redis: Redis): Redis =>
    attachLogging(redis.duplicate(), 'RedisSubscriber'),
};

// Providers construídos por factory não têm ciclo de vida próprio, então os
// sockets do ioredis ficariam abertos e o processo não encerraria no SIGTERM —
// o container levaria SIGKILL e o SessionManager perderia a chance de liberar os
// locks de ownership, prendendo cada tenant até o TTL expirar.
//
// `onApplicationShutdown` roda depois de todos os `onModuleDestroy`, então o
// Redis continua disponível enquanto o SessionManager solta os locks.
@Injectable()
export class RedisLifecycle implements OnApplicationShutdown {
  private readonly logger = new Logger(RedisLifecycle.name);

  constructor(
    @Inject(REDIS_CLIENT) private readonly redis: Redis,
    @Inject(REDIS_SUBSCRIBER) private readonly subscriber: Redis,
  ) {}

  async onApplicationShutdown(): Promise<void> {
    await Promise.all([this.close(this.subscriber), this.close(this.redis)]);
  }

  // `quit` rejeita se a conexão já caiu; nesse caso `disconnect` derruba o socket
  // de forma síncrona. Encerrar não pode falhar.
  private async close(client: Redis): Promise<void> {
    try {
      await client.quit();
    } catch (error: unknown) {
      const message = error instanceof Error ? error.message : String(error);
      this.logger.warn(
        `Graceful Redis quit failed, forcing disconnect: ${message}`,
      );
      client.disconnect();
    }
  }
}
