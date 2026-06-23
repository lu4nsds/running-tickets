interface MockSocket {
  handlers: Record<string, (arg: unknown) => void>;
  ev: { on: jest.Mock; removeAllListeners: jest.Mock };
  end: jest.Mock;
  onWhatsApp: jest.Mock;
  sendMessage: jest.Mock;
}

jest.mock('@whiskeysockets/baileys', () => {
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
      onWhatsApp: jest
        .fn()
        .mockResolvedValue([{ exists: true, jid: '5511999@s.whatsapp.net' }]),
      sendMessage: jest.fn().mockResolvedValue({ key: {}, message: {} }),
    };
    sockets.push(socket);
    return socket;
  });

  return {
    __esModule: true,
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
import * as baileys from '@whiskeysockets/baileys';
import { AppService } from '@src/app.service';
import { GatewayConfig } from '@src/config/gateway.config';
import { redisPrefix, sessionSetKey } from '@src/config/redis-keys';
import { REDIS_CLIENT } from '@src/providers/redis.provider';
import { INSTANCE_ID } from '@src/providers/instance.provider';
import { SessionBusService } from '@src/bus/session-bus.service';
import { SessionManager } from '@src/session/session-manager.service';
import { TenantConnection } from '@src/session/tenant-connection.service';

const mockMakeWASocket = baileys.default as unknown as jest.Mock;
const mockSockets = (baileys as unknown as { __sockets: MockSocket[] })
  .__sockets;

const INSTANCE = 'inst-self';
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

  keys = jest.fn().mockResolvedValue([`${redisPrefix()}:auth:t1:creds`]);
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
      await service.connect('t1');

      expect(mockMakeWASocket).toHaveBeenCalledTimes(1);
      expect(await redis.get(ownerKey('t1'))).toBe(INSTANCE);
      expect((await service.getStatus('t1')).status).toBe('connecting');
    });

    it('does not open a socket when another instance owns the tenant', async () => {
      redis.owners.set(ownerKey('t1'), 'inst-other');

      await service.connect('t1');

      expect(mockMakeWASocket).not.toHaveBeenCalled();
    });
  });

  describe('sendMessage routing', () => {
    it('forwards to the owner instance via the bus when owned elsewhere', async () => {
      redis.owners.set(ownerKey('t1'), 'inst-other');
      bus.request.mockResolvedValue({ ok: true, phone: '55' });

      const result = await service.sendMessage('t1', {
        phone: '11999998888',
        message: 'hi',
      });

      expect(bus.request).toHaveBeenCalledWith('inst-other', 'send', 't1', {
        phone: '11999998888',
        message: 'hi',
      });
      expect(result).toEqual({ ok: true, phone: '55' });
    });

    it('sends locally once connected when this instance owns the tenant', async () => {
      await service.connect('t1');
      fireOpen(lastSocket());
      await flushPromises();

      const result = await service.sendMessage('t1', {
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
      await service.connect('t1');
      expect(mockMakeWASocket).toHaveBeenCalledTimes(1);

      fireClose(lastSocket(), 500);
      await flushPromises();
      expect((await service.getStatus('t1')).status).toBe('closed');

      await jest.advanceTimersByTimeAsync(1000);
      expect(mockMakeWASocket).toHaveBeenCalledTimes(2);
    });

    it('clears credentials and requires re-pair after max attempts', async () => {
      jest.useFakeTimers();
      await service.connect('t1');

      for (let i = 0; i < 3; i++) {
        fireClose(lastSocket(), 500);
        await jest.advanceTimersByTimeAsync(attemptDelay(i));
      }
      expect(mockMakeWASocket).toHaveBeenCalledTimes(4);

      fireClose(lastSocket(), 500); // 4th attempt > maxAttempts
      await flushPromises();

      expect(redis.del).toHaveBeenCalled();
      expect((await service.getStatus('t1')).status).toBe('closed');
      // ownership released so another instance can take over
      expect(await redis.get(ownerKey('t1'))).toBeNull();

      await jest.advanceTimersByTimeAsync(60000);
      expect(mockMakeWASocket).toHaveBeenCalledTimes(4);
    });

    it('resets the attempt counter once the connection opens', async () => {
      jest.useFakeTimers();
      await service.connect('t1');

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
      await service.connect('t1');

      fireClose(lastSocket(), 440);
      await flushPromises();
      expect((await service.getStatus('t1')).status).toBe('closed');

      await jest.advanceTimersByTimeAsync(60000);
      expect(mockMakeWASocket).toHaveBeenCalledTimes(1);
    });

    it('ignores close events from a stale (replaced) socket', async () => {
      jest.useFakeTimers();
      await service.connect('t1');
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
      redis.owners.set(ownerKey('t1'), 'inst-other');
      bus.request.mockResolvedValue(undefined);

      await service.removeSession('t1');

      expect(bus.request).toHaveBeenCalledWith(
        'inst-other',
        'remove',
        't1',
        null,
      );
    });

    it('cleans Redis directly when the tenant has no live owner', async () => {
      await service.removeSession('t1');

      expect(redis.srem).toHaveBeenCalledWith(sessionSetKey(), 't1');
      expect(redis.del).toHaveBeenCalled();
    });
  });
});

// delay for the (i+1)-th attempt: base * 2^i, capped at maxDelay
function attemptDelay(i: number): number {
  return Math.min(1000 * 2 ** i, 60000);
}
