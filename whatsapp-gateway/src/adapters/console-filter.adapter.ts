import { LogNoiseAdapter } from '@src/adapters/log-noise.adapter';

export class ConsoleFilterAdapter {
  private static readonly original = {
    error: console.error.bind(console),
    log: console.log.bind(console),
    info: console.info.bind(console),
    warn: console.warn.bind(console),
    debug: console.debug.bind(console),
  };

  static apply(): void {
    console.error = (...args: unknown[]) => {
      if (LogNoiseAdapter.hasNoisyArg(args)) return;
      ConsoleFilterAdapter.original.error(...args);
    };

    console.log = (...args: unknown[]) => {
      if (LogNoiseAdapter.hasNoisyArg(args)) return;
      ConsoleFilterAdapter.original.log(...args);
    };

    console.info = (...args: unknown[]) => {
      if (LogNoiseAdapter.hasNoisyArg(args)) return;
      ConsoleFilterAdapter.original.info(...args);
    };

    console.warn = (...args: unknown[]) => {
      if (LogNoiseAdapter.hasNoisyArg(args)) return;
      ConsoleFilterAdapter.original.warn(...args);
    };

    console.debug = (...args: unknown[]) => {
      if (LogNoiseAdapter.hasNoisyArg(args)) return;
      ConsoleFilterAdapter.original.debug(...args);
    };
  }
}
