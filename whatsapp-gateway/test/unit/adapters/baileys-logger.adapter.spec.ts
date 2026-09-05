import { Logger } from '@nestjs/common';
import { BaileysLoggerAdapter } from '@src/adapters/baileys-logger.adapter';

describe('BaileysLoggerAdapter', () => {
  const makeLogger = () => {
    const mocks = {
      verbose: jest.fn(),
      debug: jest.fn(),
      log: jest.fn(),
      warn: jest.fn(),
      error: jest.fn(),
    };
    return { logger: mocks as unknown as Logger, mocks };
  };

  it('forwards a normal message to the Nest logger', () => {
    const { logger, mocks } = makeLogger();
    const pino = new BaileysLoggerAdapter(logger, 'trace').build();

    pino.error({ err: 'boom' }, 'something actionable happened');

    expect(mocks.error).toHaveBeenCalledWith('something actionable happened');
  });

  it('drops noisy "failed to decrypt message" errors', () => {
    const { logger, mocks } = makeLogger();
    const pino = new BaileysLoggerAdapter(logger, 'trace').build();

    pino.error({ key: 'x' }, 'failed to decrypt message');

    expect(mocks.error).not.toHaveBeenCalled();
  });

  it('drops noisy "url generation failed" warnings', () => {
    const { logger, mocks } = makeLogger();
    const pino = new BaileysLoggerAdapter(logger, 'trace').build();

    pino.warn({ trace: 'x' }, 'url generation failed');

    expect(mocks.warn).not.toHaveBeenCalled();
  });

  it('respects the configured minimum level', () => {
    const { logger, mocks } = makeLogger();
    const pino = new BaileysLoggerAdapter(logger, 'error').build();

    pino.debug({}, 'debug detail');

    expect(mocks.debug).not.toHaveBeenCalled();
  });
});
