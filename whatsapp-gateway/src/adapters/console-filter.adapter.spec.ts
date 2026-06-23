describe('ConsoleFilterAdapter', () => {
  afterEach(() => {
    jest.restoreAllMocks();
    jest.resetModules();
  });

  // The adapter captures the console methods at module-evaluation time, so the
  // spies must be installed before a fresh import; resetModules forces that.
  async function applyWithSpies() {
    const spies = {
      error: jest.spyOn(console, 'error').mockImplementation(() => {}),
      log: jest.spyOn(console, 'log').mockImplementation(() => {}),
      info: jest.spyOn(console, 'info').mockImplementation(() => {}),
      warn: jest.spyOn(console, 'warn').mockImplementation(() => {}),
      debug: jest.spyOn(console, 'debug').mockImplementation(() => {}),
    };
    jest.resetModules();
    const { ConsoleFilterAdapter } =
      await import('@src/adapters/console-filter.adapter');
    ConsoleFilterAdapter.apply();
    return spies;
  }

  it('drops the libsignal "Closing session:" dump on console.info', async () => {
    const spies = await applyWithSpies();

    console.info('Closing session:', { registrationId: 1 });

    expect(spies.info).not.toHaveBeenCalled();
  });

  it('forwards a normal console.info message', async () => {
    const spies = await applyWithSpies();

    console.info('WhatsApp connected');

    expect(spies.info).toHaveBeenCalledWith('WhatsApp connected');
  });

  it('drops noisy console.error and forwards normal ones', async () => {
    const spies = await applyWithSpies();

    console.error('Failed to decrypt message with any known session...');
    expect(spies.error).not.toHaveBeenCalled();

    console.error('real failure', { code: 500 });
    expect(spies.error).toHaveBeenCalledWith('real failure', { code: 500 });
  });

  it('drops noisy console.warn and forwards normal ones', async () => {
    const spies = await applyWithSpies();

    console.warn('Session already closed', {});
    expect(spies.warn).not.toHaveBeenCalled();

    console.warn('heads up');
    expect(spies.warn).toHaveBeenCalledWith('heads up');
  });

  it('forwards a normal console.log message', async () => {
    const spies = await applyWithSpies();

    console.log('plain log');

    expect(spies.log).toHaveBeenCalledWith('plain log');
  });
});
