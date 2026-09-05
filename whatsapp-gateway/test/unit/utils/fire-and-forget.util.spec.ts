import { Logger } from '@nestjs/common';
import { fireAndForget } from '@src/utils/fire-and-forget.util';

interface LoggerHarness {
  logger: Logger;
  error: jest.Mock;
}

function makeLogger(): LoggerHarness {
  const error = jest.fn();
  return { logger: { error } as unknown as Logger, error };
}

const settle = (): Promise<void> =>
  new Promise((resolve) => setImmediate(resolve));

describe('fireAndForget', () => {
  it('swallows a rejection and logs it with the given context', async () => {
    const { logger, error } = makeLogger();

    fireAndForget(
      Promise.reject(new Error('redis down')),
      logger,
      'Failed to persist credentials',
    );

    await settle();

    expect(error).toHaveBeenCalledWith(
      'Failed to persist credentials: redis down',
    );
  });

  it('stringifies non-Error rejection reasons', async () => {
    const { logger, error } = makeLogger();

    fireAndForget(Promise.reject(new Error('boom')), logger, 'Failed');

    await settle();

    expect(error).toHaveBeenCalledWith('Failed: boom');
  });

  it('stays quiet when the promise resolves', async () => {
    const { logger, error } = makeLogger();

    fireAndForget(Promise.resolve('ok'), logger, 'Failed');

    await settle();

    expect(error).not.toHaveBeenCalled();
  });

  // O motivo de existir: no Node uma rejeição não tratada encerra o processo, e
  // um único soluço do Redis num handler de evento derrubaria todos os tenants.
  it('never leaves an unhandled rejection behind', async () => {
    const { logger } = makeLogger();
    const unhandled = jest.fn();
    process.on('unhandledRejection', unhandled);

    fireAndForget(Promise.reject(new Error('nope')), logger, 'Failed');

    await new Promise((resolve) => setTimeout(resolve, 10));
    process.off('unhandledRejection', unhandled);

    expect(unhandled).not.toHaveBeenCalled();
  });

  // Um mock de spec (ou um chamador que não devolve promise) não pode explodir
  // dentro do próprio guarda.
  it('tolerates a value that is not a promise', async () => {
    const { logger, error } = makeLogger();

    expect(() =>
      fireAndForget(undefined as unknown as Promise<void>, logger, 'Failed'),
    ).not.toThrow();

    await settle();
    expect(error).not.toHaveBeenCalled();
  });
});
