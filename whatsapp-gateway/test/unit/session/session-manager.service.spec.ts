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
      DisconnectReason: { loggedOut: 401 },
      BufferJSON: { replacer: jest.fn(), reviver: jest.fn() },
      proto: {
        Message: {
          AppStateSyncKeyData: { create: jest.fn((o: object) => o) },
        },
      },
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
      saveCreds: jest.fn().mockResolvedValue(undefined),
    }),
    authPattern: (uuid: string) => `${redisPrefix()}:auth:${uuid}:*`,
  };
});

jest.mock('@src/states/redis-message.state', () => {
  const { redisPrefix } = jest.requireActual<
    typeof import('@src/config/redis-keys')
  >('@src/config/redis-keys');
  return {
    messagesPattern: (uuid: string) => `${redisPrefix()}:msgs:${uuid}:*`,
    cacheMessage: jest.fn().mockResolvedValue(undefined),
    loadCachedMessage: jest.fn(),
  };
});

import { Test, TestingModule } from '@nestjs/testing';
import { ConfigService } from '@nestjs/config';
import { GatewayConfig } from '@src/config/gateway.config';
import { redisPrefix, sessionSetKey } from '@src/config/redis-keys';
import { REDIS_CLIENT } from '@src/providers/redis.provider';
import { INSTANCE_ID } from '@src/providers/instance.provider';
import { SessionManager } from '@src/session/session-manager.service';
import { TenantConnection } from '@src/session/tenant-connection.service';
import { setSessionState } from '@src/states/redis-session.state';
import { fakeScanSupport } from '@test/helpers/fake-redis-scan';
import { importBaileys } from '@src/utils/baileys.import';

let mockMakeWASocket: jest.Mock;
let mockSockets: MockSocket[];

const INSTANCE = 'inst-self';
const SESSION_SET = sessionSetKey();
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

// Reach the private background loops directly so each can be unit-tested in
// isolation, deterministically, without juggling interval timers.
interface Internals {
  heartbeat(): Promise<void>;
  reconcile(): Promise<void>;
}
const internals = (m: SessionManager): Internals => m as unknown as Internals;

const lastSocket = (): MockSocket => mockSockets[mockSockets.length - 1];

describe('SessionManager', () => {
  let manager: SessionManager;
  let redis: FakeRedis;

  beforeEach(async () => {
    const mod = (await importBaileys()) as unknown as {
      default: jest.Mock;
      __sockets: MockSocket[];
    };
    mockMakeWASocket = mod.default;
    mockSockets = mod.__sockets;

    redis = new FakeRedis();

    const module: TestingModule = await Test.createTestingModule({
      providers: [
        SessionManager,
        TenantConnection,
        GatewayConfig,
        { provide: REDIS_CLIENT, useValue: redis },
        { provide: INSTANCE_ID, useValue: INSTANCE },
        { provide: ConfigService, useValue: mockConfigService },
      ],
    }).compile();

    manager = module.get<SessionManager>(SessionManager);
  });

  afterEach(() => {
    jest.clearAllMocks();
    jest.useRealTimers();
    mockSockets.length = 0;
  });

  describe('open', () => {
    it('builds a live connection and registers it for the tenant', async () => {
      const result = await manager.open('t1');

      expect(mockMakeWASocket).toHaveBeenCalledTimes(1);
      expect(manager.has('t1')).toBe(true);
      expect(manager.getConnection('t1')).toBeDefined();
      expect(result).toEqual({ status: 'connecting', qr: null });
    });

    it('replaces any existing connection for the same tenant', async () => {
      await manager.open('t1');
      const first = manager.getConnection('t1');

      await manager.open('t1');

      expect(mockMakeWASocket).toHaveBeenCalledTimes(2);
      expect(manager.getConnection('t1')).not.toBe(first);
    });

    it('ends the previous socket instead of leaking it', async () => {
      await manager.open('t1');
      const firstSocket = lastSocket();

      await manager.open('t1');

      expect(firstSocket.end).toHaveBeenCalled();
    });

    // Deixar a conexão morta no registro criaria um zumbi: `has()` seguiria true,
    // o heartbeat renovaria o lock indefinidamente, o reconcile pularia o tenant
    // e os envios falhariam para sempre.
    it('leaves no dead connection registered when the open fails', async () => {
      await manager.open('t1');
      redis.owners.set(ownerKey('t1'), INSTANCE);
      mockMakeWASocket.mockImplementationOnce(() => {
        throw new Error('socket build failed');
      });

      await expect(manager.open('t1')).rejects.toThrow();

      expect(manager.has('t1')).toBe(false);
      expect(manager.getConnection('t1')).toBeUndefined();
    });

    it('releases ownership when the open fails, so reconcile can retry', async () => {
      redis.owners.set(ownerKey('t1'), INSTANCE);
      mockMakeWASocket.mockImplementationOnce(() => {
        throw new Error('socket build failed');
      });

      await expect(manager.open('t1')).rejects.toThrow();

      expect(await redis.get(ownerKey('t1'))).toBeNull();
    });

    it('lets reconcile recover a tenant whose open previously failed', async () => {
      redis.sets.set(SESSION_SET, new Set(['t1']));
      redis.owners.set(ownerKey('t1'), INSTANCE);
      mockMakeWASocket.mockImplementationOnce(() => {
        throw new Error('socket build failed');
      });
      await expect(manager.open('t1')).rejects.toThrow();

      await internals(manager).reconcile();

      expect(manager.has('t1')).toBe(true);
      expect(await redis.get(ownerKey('t1'))).toBe(INSTANCE);
    });
  });

  describe('removeLocal', () => {
    it('tears down the socket and wipes every Redis trace of the tenant', async () => {
      await manager.open('t1');
      redis.owners.set(ownerKey('t1'), INSTANCE);
      jest.clearAllMocks();

      await manager.removeLocal('t1');

      expect(manager.has('t1')).toBe(false);
      expect(redis.srem).toHaveBeenCalledWith(SESSION_SET, 't1');
      // ownership released (eval del) and state hash cleared
      expect(redis.eval).toHaveBeenCalled();
      expect(await redis.get(ownerKey('t1'))).toBeNull();
      // auth + message keys removed via SCAN + UNLINK (nunca KEYS, que bloqueia
      // o Redis compartilhado com o cache e as filas do Laravel)
      expect(redis.scans.map((scan) => scan.match)).toEqual([
        `${redisPrefix()}:auth:t1:*`,
        `${redisPrefix()}:msgs:t1:*`,
      ]);
      expect(redis.unlinked).toHaveLength(2);
      expect(redis.keys).not.toHaveBeenCalled();
    });

    // Sem o logout o aparelho continua listado em "Aparelhos conectados" do
    // tenant; cada desconectar/reconectar somaria um fantasma até bater no limite
    // de dispositivos vinculados do WhatsApp e impedir novo pareamento.
    it('unlinks the device on WhatsApp before wiping the local session', async () => {
      await manager.open('t1');
      redis.owners.set(ownerKey('t1'), INSTANCE);
      const socket = lastSocket();

      await manager.removeLocal('t1');

      expect(socket.logout).toHaveBeenCalled();
      expect(socket.end).toHaveBeenCalled();
      expect(manager.has('t1')).toBe(false);
    });

    it('still wipes the session when the logout fails', async () => {
      await manager.open('t1');
      redis.owners.set(ownerKey('t1'), INSTANCE);
      lastSocket().logout.mockRejectedValue(new Error('socket already closed'));

      await expect(manager.removeLocal('t1')).resolves.toBeUndefined();

      expect(manager.has('t1')).toBe(false);
      expect(redis.unlinked).toHaveLength(2);
      expect(await redis.get(ownerKey('t1'))).toBeNull();
    });
  });

  describe('heartbeat', () => {
    it('renews the lock and refreshes state TTL for a tenant we still own', async () => {
      await manager.open('t1');
      redis.owners.set(ownerKey('t1'), INSTANCE);
      jest.clearAllMocks();

      await internals(manager).heartbeat();

      expect(manager.has('t1')).toBe(true);
      expect(lastSocket().end).not.toHaveBeenCalled();
      // state TTL kept alive
      expect(redis.pexpire).toHaveBeenCalled();
    });

    it('tears down the local socket when ownership has been lost', async () => {
      await manager.open('t1');
      const socket = lastSocket();
      // another instance now holds the lock -> renew fails
      redis.owners.set(ownerKey('t1'), 'inst-other');

      await internals(manager).heartbeat();

      expect(manager.has('t1')).toBe(false);
      expect(socket.end).toHaveBeenCalled();
    });

    it('keeps renewing the remaining tenants when one renewal throws', async () => {
      await manager.open('t1');
      await manager.open('t2');
      redis.owners.set(ownerKey('t1'), INSTANCE);
      redis.owners.set(ownerKey('t2'), INSTANCE);
      jest.clearAllMocks();

      // first renewal (t1) blows up with a Redis blip; t2 must still renew
      redis.eval.mockRejectedValueOnce(new Error('redis blip'));

      await expect(internals(manager).heartbeat()).resolves.toBeUndefined();

      expect(manager.has('t1')).toBe(true);
      expect(manager.has('t2')).toBe(true);
      // t2's renewal went through and its state TTL was refreshed
      expect(redis.eval).toHaveBeenCalledTimes(2);
      expect(redis.pexpire).toHaveBeenCalledTimes(1);
    });
  });

  describe('reconcile', () => {
    it('claims and opens a persisted session that has no live owner', async () => {
      redis.sets.set(SESSION_SET, new Set(['orphan']));

      await internals(manager).reconcile();

      expect(await redis.get(ownerKey('orphan'))).toBe(INSTANCE);
      expect(manager.has('orphan')).toBe(true);
      expect(mockMakeWASocket).toHaveBeenCalledTimes(1);
    });

    it('skips a persisted session already owned by another instance', async () => {
      redis.sets.set(SESSION_SET, new Set(['t2']));
      redis.owners.set(ownerKey('t2'), 'inst-other');

      await internals(manager).reconcile();

      expect(manager.has('t2')).toBe(false);
      expect(mockMakeWASocket).not.toHaveBeenCalled();
    });

    it('skips a session this instance already holds locally', async () => {
      await manager.open('t1');
      redis.sets.set(SESSION_SET, new Set(['t1']));
      mockMakeWASocket.mockClear();

      await internals(manager).reconcile();

      expect(mockMakeWASocket).not.toHaveBeenCalled();
    });

    it('swallows a connect failure without aborting the reconcile loop', async () => {
      redis.sets.set(SESSION_SET, new Set(['boom']));
      mockMakeWASocket.mockImplementationOnce(() => {
        throw new Error('socket build failed');
      });

      await expect(internals(manager).reconcile()).resolves.toBeUndefined();
      expect(manager.has('boom')).toBe(false);
    });

    // Escaping here would reject onModuleInit at boot and, on the interval tick,
    // surface as an unhandled rejection — which kills the whole process.
    it('never lets a failed session listing escape', async () => {
      redis.smembers.mockRejectedValueOnce(new Error('redis down'));

      await expect(internals(manager).reconcile()).resolves.toBeUndefined();
      expect(mockMakeWASocket).not.toHaveBeenCalled();
    });

    it('keeps reconciling the remaining tenants when one owner lookup throws', async () => {
      redis.sets.set(SESSION_SET, new Set(['bad', 'good']));

      // owner lookup for the first tenant blows up; the second must still be
      // claimed and opened
      redis.get.mockRejectedValueOnce(new Error('redis blip'));

      await expect(internals(manager).reconcile()).resolves.toBeUndefined();

      const claimed = [...redis.owners.values()];
      expect(claimed).toContain(INSTANCE);
      expect(mockMakeWASocket).toHaveBeenCalledTimes(1);
    });
  });

  describe('waitUntilOpen', () => {
    const stateKey = `${redisPrefix()}:state:w1`;

    it('resolves as soon as the session is open', async () => {
      await setSessionState(redis as never, 'w1', { status: 'open' }, 30000);
      await expect(manager.waitUntilOpen('w1', 5000)).resolves.toBeUndefined();
    });

    it('stops waiting on a terminal closed state (no pending reconnect)', async () => {
      await setSessionState(
        redis as never,
        'w1',
        { status: 'closed', reconnectAttempts: 0 },
        30000,
      );
      await expect(manager.waitUntilOpen('w1', 5000)).resolves.toBeUndefined();
    });

    it('keeps waiting through a backoff window, then resolves once it opens', async () => {
      jest.useFakeTimers();
      redis.hashes.set(stateKey, { status: 'closed', reconnectAttempts: '2' });

      let resolved = false;
      const pending = manager.waitUntilOpen('w1', 5000).then(() => {
        resolved = true;
      });

      await jest.advanceTimersByTimeAsync(250);
      await jest.advanceTimersByTimeAsync(250);
      expect(resolved).toBe(false);

      redis.hashes.set(stateKey, { status: 'open', reconnectAttempts: '0' });
      await jest.advanceTimersByTimeAsync(250);
      await pending;

      expect(resolved).toBe(true);
    });
  });

  describe('lifecycle', () => {
    it('reconciles at boot and schedules the heartbeat + reconcile timers', async () => {
      redis.sets.set(SESSION_SET, new Set(['orphan']));
      const setInterval = jest.spyOn(global, 'setInterval');

      await manager.onModuleInit();

      // boot reconcile claimed the orphan
      expect(await redis.get(ownerKey('orphan'))).toBe(INSTANCE);
      expect(setInterval).toHaveBeenCalledTimes(2);

      await manager.onModuleDestroy();
    });

    it('clears timers and releases ownership for every held tenant on destroy', async () => {
      await manager.open('t1');
      redis.owners.set(ownerKey('t1'), INSTANCE);
      const socket = lastSocket();
      await manager.onModuleInit();
      const clearInterval = jest.spyOn(global, 'clearInterval');

      await manager.onModuleDestroy();

      expect(clearInterval).toHaveBeenCalledTimes(2);
      expect(socket.end).toHaveBeenCalled();
      expect(manager.has('t1')).toBe(false);
      expect(await redis.get(ownerKey('t1'))).toBeNull();
    });
  });
});
