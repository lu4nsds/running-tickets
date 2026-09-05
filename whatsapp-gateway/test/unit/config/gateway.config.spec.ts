import { ConfigService } from '@nestjs/config';
import { GatewayConfig } from '@src/config/gateway.config';

const ENV: Record<string, string> = {
  WHATSAPP_CONNECT_TIMEOUT_MS: '30000',
  WHATSAPP_RECONNECT_MAX_ATTEMPTS: '3',
  WHATSAPP_RECONNECT_BASE_DELAY_MS: '1000',
  WHATSAPP_RECONNECT_MAX_DELAY_MS: '60000',
  WHATSAPP_OWNERSHIP_TTL_MS: '30000',
  WHATSAPP_HEARTBEAT_INTERVAL_MS: '10000',
  WHATSAPP_RECONCILE_INTERVAL_MS: '20000',
  WHATSAPP_RPC_TIMEOUT_MS: '45000',
  WHATSAPP_API_KEY: 'test-api-key',
  WHATSAPP_BAILEYS_LOG_LEVEL: '  error  ',
  WHATSAPP_DEVICE_NAME: 'My Device',
};

function buildConfig(overrides: Record<string, string> = {}): GatewayConfig {
  const env = { ...ENV, ...overrides };
  const config = {
    get: (key: string): string | undefined => env[key],
  } as unknown as ConfigService;
  return new GatewayConfig(config);
}

function buildWithout(key: string): () => GatewayConfig {
  const env = { ...ENV };
  delete env[key];
  return () =>
    new GatewayConfig({
      get: (name: string): string | undefined => env[name],
    } as unknown as ConfigService);
}

const NUMERIC_KEYS = [
  'WHATSAPP_CONNECT_TIMEOUT_MS',
  'WHATSAPP_RECONNECT_MAX_ATTEMPTS',
  'WHATSAPP_RECONNECT_BASE_DELAY_MS',
  'WHATSAPP_RECONNECT_MAX_DELAY_MS',
  'WHATSAPP_OWNERSHIP_TTL_MS',
  'WHATSAPP_HEARTBEAT_INTERVAL_MS',
  'WHATSAPP_RECONCILE_INTERVAL_MS',
  'WHATSAPP_RPC_TIMEOUT_MS',
];

describe('GatewayConfig', () => {
  it('maps every numeric env var to a number', () => {
    const config = buildConfig();

    expect(config.connectTimeoutMs).toBe(30000);
    expect(config.reconnectMaxAttempts).toBe(3);
    expect(config.reconnectBaseDelayMs).toBe(1000);
    expect(config.reconnectMaxDelayMs).toBe(60000);
    expect(config.ownershipTtlMs).toBe(30000);
    expect(config.heartbeatIntervalMs).toBe(10000);
    expect(config.reconcileIntervalMs).toBe(20000);
    expect(config.rpcTimeoutMs).toBe(45000);
  });

  // Sem esta validação, `Number(undefined)` vira NaN e cada consumidor degrada em
  // silêncio de um jeito diferente: loop de reconexão infinito, `pexpire NaN`
  // falhando em toda escrita de estado, `setTimeout(fn, NaN)` disparando na hora.
  describe('numeric env validation', () => {
    it.each(NUMERIC_KEYS)('throws when %s is missing', (key) => {
      expect(buildWithout(key)).toThrow(`Missing or invalid env var: ${key}`);
    });

    it.each(['', 'abc', '0', '-1'])(
      'throws when a numeric env var is %p',
      (value) => {
        expect(() => buildConfig({ WHATSAPP_OWNERSHIP_TTL_MS: value })).toThrow(
          'Missing or invalid env var: WHATSAPP_OWNERSHIP_TTL_MS',
        );
      },
    );
  });

  // O guard é fail-closed: sem chave, nenhuma requisição passa. Ela precisa ser
  // exigida no boot, senão o serviço sobe sem autenticação nenhuma.
  it('exposes the API key and throws when it is missing', () => {
    expect(buildConfig().apiKey).toBe('test-api-key');
    expect(buildWithout('WHATSAPP_API_KEY')).toThrow(
      'Missing required env var: WHATSAPP_API_KEY',
    );
  });

  it('trims the optional Baileys log level', () => {
    expect(buildConfig().baileysLogLevel).toBe('error');
  });

  it('leaves the log level undefined when not configured', () => {
    const env = { ...ENV };
    delete env.WHATSAPP_BAILEYS_LOG_LEVEL;
    const config = new GatewayConfig({
      get: (key: string): string | undefined => env[key],
    } as unknown as ConfigService);

    expect(config.baileysLogLevel).toBeUndefined();
  });

  it('exposes the device name from WHATSAPP_DEVICE_NAME', () => {
    expect(buildConfig().deviceName).toBe('My Device');
  });

  it('throws when WHATSAPP_DEVICE_NAME is missing', () => {
    const env = { ...ENV };
    delete env.WHATSAPP_DEVICE_NAME;
    expect(
      () =>
        new GatewayConfig({
          get: (key: string): string | undefined => env[key],
        } as unknown as ConfigService),
    ).toThrow('Missing required env var: WHATSAPP_DEVICE_NAME');
  });

  it('fails fast at construction when REDIS_PREFIX is missing', () => {
    const original = process.env.REDIS_PREFIX;
    delete process.env.REDIS_PREFIX;
    try {
      expect(() => buildConfig()).toThrow(
        'REDIS_PREFIX environment variable is required',
      );
    } finally {
      if (original === undefined) delete process.env.REDIS_PREFIX;
      else process.env.REDIS_PREFIX = original;
    }
  });
});
