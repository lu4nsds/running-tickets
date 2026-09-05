import {
  Controller,
  Get,
  HttpStatus,
  Inject,
  Logger,
  ServiceUnavailableException,
} from '@nestjs/common';
import type Redis from 'ioredis';
import { Public } from '@src/guards/api-key.guard';
import { REDIS_CLIENT } from '@src/providers/redis.provider';

// Um ping que demora mais que isto vale como Redis indisponível: sem o teto, o
// health check herdaria a fila offline do ioredis e ficaria pendurado, e o
// orquestrador leria timeout em vez de "unhealthy".
const PING_TIMEOUT_MS = 2_000;

@Public()
@Controller('health')
export class HealthController {
  private readonly logger = new Logger(HealthController.name);

  constructor(@Inject(REDIS_CLIENT) private readonly redis: Redis) {}

  // Todo estado observável de sessão vive no Redis: sem ele o gateway não sabe
  // quem é dono de qual tenant, não persiste credenciais e não envia nada. Um
  // `{status:'ok'}` incondicional faria o orquestrador manter tráfego numa
  // instância que não consegue atender a nenhuma requisição.
  @Get()
  async check(): Promise<{ status: string; redis: string }> {
    try {
      await this.withTimeout(this.redis.ping());
    } catch (error: unknown) {
      const message = error instanceof Error ? error.message : String(error);
      this.logger.error(`Health check failed: ${message}`);

      throw new ServiceUnavailableException({
        statusCode: HttpStatus.SERVICE_UNAVAILABLE,
        status: 'error',
        redis: 'unavailable',
      });
    }

    return { status: 'ok', redis: 'ok' };
  }

  private withTimeout(promise: Promise<string>): Promise<string> {
    return new Promise<string>((resolve, reject) => {
      const timer = setTimeout(
        () =>
          reject(new Error(`Redis ping timed out after ${PING_TIMEOUT_MS}ms`)),
        PING_TIMEOUT_MS,
      );

      promise
        .then((value) => resolve(value))
        .catch((error: unknown) =>
          reject(error instanceof Error ? error : new Error(String(error))),
        )
        .finally(() => clearTimeout(timer));
    });
  }
}
