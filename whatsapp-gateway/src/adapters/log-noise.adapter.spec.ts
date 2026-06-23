import { LogNoiseAdapter } from '@src/adapters/log-noise.adapter';

describe('LogNoiseAdapter', () => {
  describe('isNoisy', () => {
    it.each([
      'Closing session: SessionEntry { foo: 1 }',
      'Opening session:',
      'Removing old closed session:',
      'failed to decrypt message',
      'Failed to decrypt message with any known session...',
      'url generation failed',
      "unexpected error in 'init queries'",
      'Bad MAC',
    ])('flags noisy message: %s', (message) => {
      expect(LogNoiseAdapter.isNoisy(message)).toBe(true);
    });

    it('does not flag a normal message', () => {
      expect(LogNoiseAdapter.isNoisy('WhatsApp connected')).toBe(false);
    });

    it('does not flag non-string values', () => {
      expect(LogNoiseAdapter.isNoisy({ foo: 'Closing session:' })).toBe(false);
      expect(LogNoiseAdapter.isNoisy(undefined)).toBe(false);
      expect(LogNoiseAdapter.isNoisy(42)).toBe(false);
    });
  });

  describe('hasNoisyArg', () => {
    it('flags when any string arg is noisy (e.g. console.info dump)', () => {
      expect(
        LogNoiseAdapter.hasNoisyArg([
          'Closing session:',
          { registrationId: 1 },
        ]),
      ).toBe(true);
    });

    it('does not flag when no arg matches', () => {
      expect(
        LogNoiseAdapter.hasNoisyArg(['WhatsApp connected', { ok: true }]),
      ).toBe(false);
    });
  });
});
