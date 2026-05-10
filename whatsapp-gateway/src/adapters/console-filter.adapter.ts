const BAILEYS_NOISE = [
  'Failed to decrypt message',
  'Session error:',
  'Closing session:',
  'Removing old closed session:',
  'Bad MAC',
];

export class ConsoleFilterAdapter {
  private static readonly originalError = console.error.bind(console);
  private static readonly originalLog = console.log.bind(console);

  static apply(): void {
    console.error = (...args: unknown[]) => {
      if (ConsoleFilterAdapter.isNoise(args)) return;
      ConsoleFilterAdapter.originalError(...args);
    };

    console.log = (...args: unknown[]) => {
      if (ConsoleFilterAdapter.isNoise(args)) return;
      ConsoleFilterAdapter.originalLog(...args);
    };
  }

  private static isNoise(args: unknown[]): boolean {
    return args.some(
      (arg) =>
        typeof arg === 'string' &&
        BAILEYS_NOISE.some((pattern) => arg.includes(pattern)),
    );
  }
}
