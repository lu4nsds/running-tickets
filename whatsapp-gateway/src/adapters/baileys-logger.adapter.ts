import { Logger } from '@nestjs/common';
import type { Logger as PinoLogger } from 'pino';
import { LogNoiseAdapter } from '@src/adapters/log-noise.adapter';

export class BaileysLoggerAdapter {
  private readonly minIndex: number;
  private static readonly levels = [
    'trace',
    'debug',
    'info',
    'warn',
    'error',
    'fatal',
  ];

  constructor(
    private readonly logger: Logger,
    private readonly minLevel: string | undefined,
  ) {
    this.minIndex = BaileysLoggerAdapter.levels.indexOf(minLevel ?? '0');
  }

  build(): PinoLogger {
    return {
      level: this.minLevel,
      trace: this.handle('verbose', 0),
      debug: this.handle('debug', 1),
      info: this.handle('log', 2),
      warn: this.handle('warn', 3),
      error: this.handle('error', 4),
      fatal: this.handle('error', 5),
      child: () => new BaileysLoggerAdapter(this.logger, this.minLevel).build(),
      flush: () => {},
      silent: () => {},
    } as unknown as PinoLogger;
  }

  private handle(
    method: 'verbose' | 'debug' | 'log' | 'warn' | 'error',
    levelIndex: number,
  ) {
    return (obj: unknown, msg?: string) => {
      if (levelIndex < this.minIndex) return;
      const message =
        msg ?? (typeof obj === 'string' ? obj : JSON.stringify(obj));
      if (LogNoiseAdapter.isNoisy(message)) return;
      this.logger[method](message);
    };
  }
}
