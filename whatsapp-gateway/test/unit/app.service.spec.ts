interface MockSocket {
  handlers: Record<string, (arg: unknown) => void>;
  ev: { on: jest.Mock; removeAllListeners: jest.Mock };
  end: jest.Mock;
  logout: jest.Mock;
  onWhatsApp: jest.Mock;
  sendMessage: jest.Mock;
}

jest.mock('@src/utils/baileys.import', () => {
  const sockets: MockSocket[] = [];
  const make = jest.fn((): MockSocket => {
    const handlers: Record<string, (arg: unknown) => void> = {};
    const socket: MockSocket = {
      handlers,
      ev: {
        on: jest.fn((event: string, cb: (arg: unknown) => void) => {
          handlers[event] = cb;
        }),
        removeAllListeners: jest.fn(),
      },
      end: jest.fn(),
      logout: jest.fn().mockResolvedValue(undefined),
      onWhatsApp: jest
        .fn()
        .mockResolvedValue([{ exists: true, jid: '5511999@s.whatsapp.net' }]),
      sendMessage: jest.fn().mockResolvedValue({ key: {}, message: {} }),
    };
    sockets.push(socket);
    return socket;
  });

  return {
    importBaileys: jest.fn().mockResolvedValue({
      default: make,
      __sockets: sockets,
      fetchLatestBaileysVersion: jest
        .fn()
        .mockResolvedValue({ version: [2, 3, 0] }),
      DisconnectReason: {
        loggedOut: 401,
        connectionClosed: 428,
        connectionLost: 408,
        connectionReplaced: 440,
        badSession: 500,
        restartRequired: 515,
      },
      BufferJSON: { replacer: jest.fn(), reviver: jest.fn() },
      proto: {},
    }),
  };
});

jest.mock('@src/states/redis-auth.state', () => {
  const { redisPrefix } = jest.requireActual<
    typeof import('@src/config/redis-keys')
  >('@src/config/redis-keys');
  return {
    useRedisAuthState: jest.fn().mockResolvedValue({
      state: { creds: {}, keys: { get: jest.fn(), set: jest.fn() } },
      saveCreds: jest.fn(),
    }),
    authPattern: (uuid: string) => `${redisPrefix()}:auth:${uuid}:*`,
  };
});

import { Test, TestingModule } from '@nestjs/testing';
import { ConfigService } from '@nestjs/config';
import { AppService } from '@src/app.service';
import { GatewayConfig } from '@src/config/gateway.config';
import { redisPrefix, sessionSetKey } from '@src/config/redis-keys';
import { REDIS_CLIENT } from '@src/providers/redis.provider';
import { INSTANCE_ID } from '@src/providers/instance.provider';
import { SessionBusService } from '@src/bus/session-bus.service';
import { SessionManager } from '@src/session/session-manager.service';
import { TenantConnection } from '@src/session/tenant-connection.service';
import { fakeScanSupport } from '@test/helpers/fake-redis-scan';
import { importBaileys } from '@src/utils/baileys.import';

let mockMakeWASocket: jest.Mock;
let mockSockets: MockSocket[];

const INSTANCE = 'inst-self';
// Precisa ser um UUID de verdade: os construtores de padrão do Redis recusam
// qualquer outra coisa (ver `assertTenantUuid`).
const TENANT = '3f2504e0-4f89-41d3-9a0c-0305e82c3301';
const ownerKey = (uuid: string): string => `${redisPrefix()}:owner:${uuid}`;

// A small stateful in-memory Redis fake so the real ownership/session state
// modules exercise their actual logic (claims, hashes, ttl) against it.
class FakeRedis {
  hashes = new Map<string, Record<string, string>>();
  owners = new Map<string, string>();
  sets = new Map<string, Set<string>>();

  set = jest.fn(
    (
      key: string,
      value: string,
      ...args: unknown[]
    ): Promise<string | null> => {
      const nx = args.includes('NX');
      if (key.startsWith(`${redisPrefix()}:owner:`)) {
        if (nx && this.owners.has(key)) return Promise.resolve(null);
        this.owners.set(key, value);
      }
      return Promise.resolve('OK');
    },
  );

  get = jest.fn(
    (key: string): Promise<string | null> =>
      Promise.resolve(this.owners.get(key) ?? null),
  );

  eval = jest.fn(
    (script: string, _n: number, key: string, arg: string): Promise<number> => {
      const current = this.owners.get(key);
      if (current !== arg) return Promise.resolve(0);
      if (script.includes('del')) this.owners.delete(key);
      return Promise.resolve(1);
    },
  );

  hset = jest.fn(
    (key: string, fields: Record<string, string>): Promise<number> => {
      this.hashes.set(key, { ...(this.hashes.get(key) ?? {}), ...fields });
      return Promise.resolve(1);
    },
  );

  hgetall = jest.fn(
    (key: string): Promise<Record<string, string>> =>
      Promise.resolve(this.hashes.get(key) ?? {}),
  );

  pexpire = jest.fn().mockResolvedValue(1);

  del = jest.fn((...keys: string[]): Promise<number> => {
    for (const key of keys) {
      this.hashes.delete(key);
      this.owners.delete(key);
    }
    return Promise.resolve(keys.length);
  });

  smembers = jest.fn(
    (key: string): Promise<string[]> =>
      Promise.resolve([...(this.sets.get(key) ?? [])]),
  );

  sadd = jest.fn((key: string, member: string): Promise<number> => {
    const set = this.sets.get(key) ?? new Set<string>();
    set.add(member);
    this.sets.set(key, set);
    return Promise.resolve(1);
  });

  srem = jest.fn((key: string, member: string): Promise<number> => {
    this.sets.get(key)?.delete(member);
    return Promise.resolve(1);
  });

  // Presente só para provar que a limpeza NUNCA usa o KEYS bloqueante.
  keys = jest.fn();

  private readonly scan = fakeScanSupport();
  scans = this.scan.scans;
  unlinked = this.scan.unlinked;
  scanStream = this.scan.scanStream;
  pipeline = this.scan.pipeline;
}

const CONFIG: Record<string, string> = {
  WHATSAPP_CONNECT_TIMEOUT_MS: '30000',
  WHATSAPP_BAILEYS_LOG_LEVEL: 'error',
  WHATSAPP_RECONNECT_MAX_ATTEMPTS: '3',
  WHATSAPP_RECONNECT_BASE_DELAY_MS: '1000',
  WHATSAPP_RECONNECT_MAX_DELAY_MS: '60000',
  WHATSAPP_OWNERSHIP_TTL_MS: '30000',
  WHATSAPP_HEARTBEAT_INTERVAL_MS: '10000',
  WHATSAPP_RECONCILE_INTERVAL_MS: '20000',
  WHATSAPP_RPC_TIMEOUT_MS: '45000',
  WHATSAPP_API_KEY: 'test-api-key',
  WHATSAPP_DEVICE_NAME: 'Test Device',
};

const mockConfigService = {
  get: jest.fn((key: string, def?: string) => CONFIG[key] ?? def),
};

const flushPromises = async (): Promise<void> => {
  for (let i = 0; i < 12; i++) await Promise.resolve();
};

const lastSocket = (): MockSocket => mockSockets[mockSockets.length - 1];

const fireClose = (socket: MockSocket, statusCode: number): void => {
  socket.handlers['connection.update']({
    connection: 'close',
    lastDisconnect: { error: { output: { statusCode } } },
  });
};

const fireOpen = (socket: MockSocket): void => {
  socket.handlers['connection.update']({ connection: 'open' });
};

describe('AppService', () => {
  let service: AppService;
  let redis: FakeRedis;
  let bus: { registerHandler: jest.Mock; request: jest.Mock };

  beforeEach(async () => {
    const mod = (await importBaileys()) as unknown as {
      default: jest.Mock;
      __sockets: MockSocket[];
    };
    mockMakeWASocket = mod.default;
    mockSockets = mod.__sockets;

    redis = new FakeRedis();
    bus = { registerHandler: jest.fn(), request: jest.fn() };

    const module: TestingModule = await Test.createTestingModule({
      providers: [
        AppService,
        SessionManager,
        TenantConnection,
        GatewayConfig,
        { provide: REDIS_CLIENT, useValue: redis },
        { provide: INSTANCE_ID, useValue: INSTANCE },
        { provide: SessionBusService, useValue: bus },
        { provide: ConfigService, useValue: mockConfigService },
      ],
    }).compile();

    service = module.get<AppService>(AppService);
  });

  afterEach(() => {
    jest.clearAllMocks();
    jest.useRealTimers();
    mockSockets.length = 0;
  });

  it('registers the bus handler on construction', () => {
    expect(bus.registerHandler).toHaveBeenCalledTimes(1);
  });

  describe('getStatus', () => {
    it('returns closed status for an unknown tenant (read from Redis)', async () => {
      const result = await service.getStatus('unknown-uuid');
      expect(result).toEqual({ status: 'closed', qr: null });
    });
  });

  describe('connect / ownership', () => {
    it('claims an unowned tenant and opens a socket locally', async () => {
      await service.connect(TENANT);

      expect(mockMakeWASocket).toHaveBeenCalledTimes(1);
      expect(await redis.get(ownerKey(TENANT))).toBe(INSTANCE);
      expect((await service.getStatus(TENANT)).status).toBe('connecting');
    });

    // Devolver só o status deixaria um tenant parado em closed/error na outra
    // instância impossível de reparear daqui: o usuário clicaria em "Conectar"
    // para sempre sem receber QR. Quem gera o QR é a dona do socket.
    it('asks the owning instance to reopen instead of answering with a stale status', async () => {
      redis.owners.set(ownerKey(TENANT), 'inst-other');
      bus.request.mockResolvedValue({ status: 'connecting', qr: 'qr-code' });

      const result = await service.connect(TENANT);

      expect(bus.request).toHaveBeenCalledWith(
        'inst-other',
        'connect',
        TENANT,
        null,
      );
      expect(result).toEqual({ status: 'connecting', qr: 'qr-code' });
      expect(mockMakeWASocket).not.toHaveBeenCalled();
    });

    it('opens locally when the bus asks this instance to connect', async () => {
      const [[handler]] = bus.registerHandler.mock.calls as [
        [(a: string, t: string, p: unknown) => Promise<unknown>],
      ];

      const result = await handler('connect', TENANT, null);

      expect(mockMakeWASocket).toHaveBeenCalledTimes(1);
      expect(result).toEqual({ status: 'connecting', qr: null });
    });
  });

  // Uma instância que morreu mas cujo lock ainda não expirou engoliria toda
  // requisição do tenant até o timeout de RPC (45s) — e o timeout HTTP do Laravel
  // é 60s, então o lembrete se perderia.
  describe('rpc failure fallback', () => {
    it('takes over locally when the RPC fails and the owner lock has expired', async () => {
      // sessão já viva aqui (herdada por um reconcile anterior)
      await service.connect(TENANT);
      fireOpen(lastSocket());
      await flushPromises();

      // o lock passou para uma instância que então morreu
      redis.owners.set(ownerKey(TENANT), 'inst-dead');
      bus.request.mockImplementation(() => {
        // o lock da instância morta expira enquanto o RPC estoura
        redis.owners.delete(ownerKey(TENANT));
        return Promise.reject(new Error('RPC send timed out'));
      });

      const result = await service.sendMessage(TENANT, {
        phone: '11999998888',
        message: 'hi',
      });

      expect(bus.request).toHaveBeenCalled();
      expect(await redis.get(ownerKey(TENANT))).toBe(INSTANCE);
      expect(lastSocket().sendMessage).toHaveBeenCalled();
      expect(result.ok).toBe(true);
    });

    it('propagates the error when the owner is still alive', async () => {
      redis.owners.set(ownerKey(TENANT), 'inst-other');
      bus.request.mockRejectedValue(new Error('recipient has no WhatsApp'));

      await expect(
        service.sendMessage(TENANT, { phone: '11999998888', message: 'hi' }),
      ).rejects.toThrow('recipient has no WhatsApp');

      // não roubamos o tenant de uma instância viva
      expect(await redis.get(ownerKey(TENANT))).toBe('inst-other');
      expect(mockMakeWASocket).not.toHaveBeenCalled();
    });
  });

  describe('sendMessage routing', () => {
    it('forwards to the owner instance via the bus when owned elsewhere', async () => {
      redis.owners.set(ownerKey(TENANT), 'inst-other');
      bus.request.mockResolvedValue({ ok: true, phone: '55' });

      const result = await service.sendMessage(TENANT, {
        phone: '11999998888',
        message: 'hi',
      });

      expect(bus.request).toHaveBeenCalledWith('inst-other', 'send', TENANT, {
        phone: '11999998888',
        message: 'hi',
      });
      expect(result).toEqual({ ok: true, phone: '55' });
    });

    it('sends locally once connected when this instance owns the tenant', async () => {
      await service.connect(TENANT);
      fireOpen(lastSocket());
      await flushPromises();

      const result = await service.sendMessage(TENANT, {
        phone: '11999998888',
        message: 'hi',
      });

      expect(bus.request).not.toHaveBeenCalled();
      expect(lastSocket().sendMessage).toHaveBeenCalled();
      expect(result.ok).toBe(true);
    });
  });

  describe('reconnect resilience', () => {
    it('schedules a backoff reconnect on badSession (500)', async () => {
      jest.useFakeTimers();
      await service.connect(TENANT);
      expect(mockMakeWASocket).toHaveBeenCalledTimes(1);

      fireClose(lastSocket(), 500);
      await flushPromises();
      expect((await service.getStatus(TENANT)).status).toBe('closed');

      await jest.advanceTimersByTimeAsync(1000);
      expect(mockMakeWASocket).toHaveBeenCalledTimes(2);
    });

    // Uma indisponibilidade prolongada (WhatsApp, Redis ou rede) não pode custar
    // ao dono do salão um novo pareamento por QR — o gateway segue tentando com
    // as credenciais preservadas, agora no teto do backoff.
    it('keeps retrying with credentials intact past the max attempts', async () => {
      jest.useFakeTimers();
      await service.connect(TENANT);

      for (let i = 0; i < 3; i++) {
        fireClose(lastSocket(), 500);
        await jest.advanceTimersByTimeAsync(attemptDelay(i));
      }
      expect(mockMakeWASocket).toHaveBeenCalledTimes(4);

      fireClose(lastSocket(), 500); // 4ª tentativa > maxAttempts
      await flushPromises();

      // nada foi apagado e o lock continua nesta instância
      expect(redis.unlinked).toEqual([]);
      expect(await redis.get(ownerKey(TENANT))).toBe(INSTANCE);
      expect((await service.getStatus(TENANT)).status).toBe('closed');

      // e a reconexão continua, no teto do backoff
      await jest.advanceTimersByTimeAsync(60000);
      expect(mockMakeWASocket).toHaveBeenCalledTimes(5);
    });

    it('still wipes credentials and releases the lock on a real logout', async () => {
      jest.useFakeTimers();
      await service.connect(TENANT);

      fireClose(lastSocket(), 401);
      await flushPromises();

      expect(redis.unlinked.length).toBeGreaterThan(0);
      expect((await service.getStatus(TENANT)).status).toBe('closed');
      // ownership released so another instance can take over
      expect(await redis.get(ownerKey(TENANT))).toBeNull();

      await jest.advanceTimersByTimeAsync(60000);
      expect(mockMakeWASocket).toHaveBeenCalledTimes(1);
    });

    it('resets the attempt counter once the connection opens', async () => {
      jest.useFakeTimers();
      await service.connect(TENANT);

      fireClose(lastSocket(), 500); // attempt 1, delay 1000
      await jest.advanceTimersByTimeAsync(1000);
      expect(mockMakeWASocket).toHaveBeenCalledTimes(2);

      fireOpen(lastSocket()); // resets attempts to 0
      await flushPromises();
      fireClose(lastSocket(), 500); // attempt 1 again -> base delay 1000

      await jest.advanceTimersByTimeAsync(1000);
      expect(mockMakeWASocket).toHaveBeenCalledTimes(3);
    });

    it('does not reconnect when the connection is replaced (440)', async () => {
      jest.useFakeTimers();
      await service.connect(TENANT);

      fireClose(lastSocket(), 440);
      await flushPromises();
      expect((await service.getStatus(TENANT)).status).toBe('closed');

      await jest.advanceTimersByTimeAsync(60000);
      expect(mockMakeWASocket).toHaveBeenCalledTimes(1);
    });

    it('ignores close events from a stale (replaced) socket', async () => {
      jest.useFakeTimers();
      await service.connect(TENANT);
      const staleSocket = lastSocket();

      fireClose(staleSocket, 500);
      await jest.advanceTimersByTimeAsync(1000);
      expect(mockMakeWASocket).toHaveBeenCalledTimes(2);

      fireClose(staleSocket, 500);
      await jest.advanceTimersByTimeAsync(60000);
      expect(mockMakeWASocket).toHaveBeenCalledTimes(2);
    });
  });

  describe('removeSession', () => {
    it('forwards removal to the owner instance when owned elsewhere', async () => {
      redis.owners.set(ownerKey(TENANT), 'inst-other');
      bus.request.mockResolvedValue(undefined);

      await service.removeSession(TENANT);

      expect(bus.request).toHaveBeenCalledWith(
        'inst-other',
        'remove',
        TENANT,
        null,
      );
    });

    it('cleans Redis directly when the tenant has no live owner', async () => {
      await service.removeSession(TENANT);

      expect(redis.srem).toHaveBeenCalledWith(sessionSetKey(), TENANT);
      expect(redis.del).toHaveBeenCalled();
    });
  });
});

// delay for the (i+1)-th attempt: base * 2^i, capped at maxDelay
function attemptDelay(i: number): number {
  return Math.min(1000 * 2 ** i, 60000);
}
