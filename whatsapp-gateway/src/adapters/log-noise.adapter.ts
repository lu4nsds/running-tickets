/**
 * Benign chatter emitted by libsignal (via console.*) and Baileys (via its
 * logger) during normal Signal session lifecycle and message sync. These lines
 * flood the logs without being actionable, so both the ConsoleFilterAdapter
 * and the BaileysLoggerAdapter drop any message matching one of these patterns.
 */
export class LogNoiseAdapter {
  private static readonly patterns = [
    // libsignal — session lifecycle (often dumps a full SessionEntry object)
    'Closing session:',
    'Opening session:',
    'Closing open session',
    'Closing stale open session',
    'Removing old closed session:',
    'Migrating session to:',
    'Session already closed',
    'Session already open',
    'Session error:',
    'Decrypted message with closed session',
    'Bad MAC',
    // libsignal / Baileys — decryption failures (both capitalizations)
    'Failed to decrypt message',
    'failed to decrypt message',
    // Baileys — non-actionable warnings/errors during sync
    'url generation failed',
    "unexpected error in 'init queries'",
  ];

  static isNoisy(value: unknown): boolean {
    return (
      typeof value === 'string' &&
      LogNoiseAdapter.patterns.some((pattern) => value.includes(pattern))
    );
  }

  static hasNoisyArg(args: unknown[]): boolean {
    return args.some((arg) => LogNoiseAdapter.isNoisy(arg));
  }
}
