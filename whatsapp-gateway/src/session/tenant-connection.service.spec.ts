interface MockSocket {
  config: Record<string, unknown>;
  handlers: Record<string, (arg: unknown) => void>;
  ev: { on: jest.Mock; removeAllListeners: jest.Mock };
  end: jest.Mock;
  onWhatsApp: jest.Mock;
  sendMessage: jest.Mock;
}

jest.mock('@whiskeysockets/baileys', () => {
  const sockets: MockSocket[] = [];
  const make = jest.fn((config: Record<string, unknown>): MockSocket => {
    const handlers: Record<string, (arg: unknown) => void> = {};
    const socket: MockSocket = {
      config,
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
      sendMessage: jest.fn().mockResolvedValue({
        key: { remoteJid: '5511999@s.whatsapp.net', id: 'm1' },
        message: {},
      }),
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
    DisconnectReason: { loggedOut: 401, connectionReplaced: 440 },
    BufferJSON: { replacer: jest.fn(), reviver: jest.fn() },
    proto: { Message: { fromObject: jest.fn(() => ({ conversation: '' })) } },
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

const mockCacheMessage = jest.fn();
const mockLoadCachedMessage = jest
  .fn()
  .mockResolvedValue({ conversation: 'cached' });
jest.mock('@src/states/redis-message.state', () => {
  const { redisPrefix } = jest.requireActual<
    typeof import('@src/config/redis-keys')
  >('@src/config/redis-keys');
  return {
    messagesPattern: (uuid: string) => `${redisPrefix()}:msgs:${uuid}:*`,
    cacheMessage: mockCacheMessage,
    loadCachedMessage: mockLoadCachedMessage,
  };
});

import {
  BadRequestException,
  InternalServerErrorException,
} from '@nestjs/common';
import * as baileys from '@whiskeysockets/baileys';
import { GatewayConfig } from '@src/config/gateway.config';
import { redisPrefix, sessionSetKey } from '@src/config/redis-keys';
import { TenantConnection } from '@src/session/tenant-connection.service';
import type { TenantConnectionHooks } from '@src/session/tenant-connection.service';
import { useRedisAuthState } from '@src/states/redis-auth.state';

const mockMakeWASocket = baileys.default as unknown as jest.Mock;
const mockSockets = (baileys as unknown as { __sockets: MockSocket[] })
  .__sockets;
const mockUseRedisAuthState = useRedisAuthState as jest.Mock;

const INSTANCE = 'inst-self';
const TENANT = 't1';
const stateKey = `${redisPrefix()}:state:${TENANT}`;
const ownerKey = `${redisPrefix()}:owner:${TENANT}`;

class FakeRedis {
  hashes = new Map<string, Record<string, string>>();
  owners = new Map<string, string>([[ownerKey, INSTANCE]]);
  sets = new Map<string, Set<string>>();

  set = jest.fn().mockResolvedValue('OK');
  get = jest.fn(
    (key: string): Promise<string | null> =>
      Promise.resolve(this.owners.get(key) ?? null),
  );
  eval = jest.fn((script: string, _n: number, key: string, arg: string) => {
    if (this.owners.get(key) !== arg) return Promise.resolve(0);
    if (script.includes('del')) this.owners.delete(key);
    return Promise.resolve(1);
  });
  hset = jest.fn((key: string, fields: Record<string, string>) => {
    this.hashes.set(key, { ...(this.hashes.get(key) ?? {}), ...fields });
    return Promise.resolve(1);
  });
  hgetall = jest.fn((key: string) =>
    Promise.resolve(this.hashes.get(key) ?? {}),
  );
  pexpire = jest.fn().mockResolvedValue(1);
  del = jest.fn((...keys: string[]) => {
    for (const key of keys) {
      this.hashes.delete(key);
      this.owners.delete(key);
    }
    return Promise.resolve(keys.length);
  });
  sadd = jest.fn((key: string, member: string) => {
    const set = this.sets.get(key) ?? new Set<string>();
    set.add(member);
    this.sets.set(key, set);
    return Promise.resolve(1);
  });
  srem = jest.fn((key: string, member: string) => {
    this.sets.get(key)?.delete(member);
    return Promise.resolve(1);
  });
  keys = jest.fn((pattern: string) =>
    Promise.resolve([pattern.replace('*', 'creds')]),
  );
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

const buildConfig = (): GatewayConfig =>
  new GatewayConfig({
    get: (key: string, def?: string) => CONFIG[key] ?? def,
  } as never);

const lastSocket = (): MockSocket => mockSockets[mockSockets.length - 1];

const fireClose = (socket: MockSocket, statusCode?: number): void => {
  socket.handlers['connection.update']({
    connection: 'close',
    lastDisconnect: { error: { output: { statusCode } } },
  });
};

const flushPromises = async (): Promise<void> => {
  for (let i = 0; i < 12; i++) await Promise.resolve();
};

describe('TenantConnection', () => {
  let conn: TenantConnection;
  let redis: FakeRedis;
  let hooks: { isActive: jest.Mock; reopen: jest.Mock; deregister: jest.Mock };

  beforeEach(() => {
    redis = new FakeRedis();
    conn = new TenantConnection(redis as never, INSTANCE, buildConfig());
    hooks = {
      isActive: jest.fn().mockReturnValue(true),
      reopen: jest.fn().mockResolvedValue(undefined),
      deregister: jest.fn(),
    };
    conn.bind(TENANT, hooks as unknown as TenantConnectionHooks);
  });

  afterEach(() => {
    jest.clearAllMocks();
    jest.useRealTimers();
    mockSockets.length = 0;
    mockUseRedisAuthState.mockResolvedValue({
      state: { creds: {}, keys: { get: jest.fn(), set: jest.fn() } },
      saveCreds: jest.fn(),
    });
  });

  describe('open', () => {
    it('wires the socket events and marks the session connecting', async () => {
      await conn.open();

      expect(mockMakeWASocket).toHaveBeenCalledTimes(1);
      expect(redis.hashes.get(stateKey)?.status).toBe('connecting');
      expect(lastSocket().config.browser).toEqual([
        'Test Device',
        'Chrome',
        '1.0.0',
      ]);
      expect(lastSocket().ev.on).toHaveBeenCalledWith(
        'connection.update',
        expect.any(Function),
      );
    });

    it('sets error state and throws when the socket cannot be built', async () => {
      mockUseRedisAuthState.mockRejectedValueOnce(new Error('redis down'));

      await expect(conn.open()).rejects.toBeInstanceOf(
        InternalServerErrorException,
      );
      expect(redis.hashes.get(stateKey)?.status).toBe('error');
    });

    it('replays a cached message through the getMessage hook', async () => {
      await conn.open();
      const getMessage = lastSocket().config.getMessage as (key: {
        remoteJid: string;
        id: string;
      }) => Promise<unknown>;

      const result = await getMessage({ remoteJid: 'jid', id: 'm1' });

      expect(mockLoadCachedMessage).toHaveBeenCalledWith(
        redis,
        TENANT,
        'jid',
        'm1',
      );
      expect(result).toEqual({ conversation: 'cached' });
    });
  });

  describe('events', () => {
    it('persists the QR code when one is emitted', async () => {
      await conn.open();
      lastSocket().handlers['connection.update']({ qr: 'qr-code-data' });
      await flushPromises();

      expect(redis.hashes.get(stateKey)?.qr).toBe('qr-code-data');
    });

    it('marks the session open and adds it to the session set', async () => {
      await conn.open();
      lastSocket().handlers['connection.update']({ connection: 'open' });
      await flushPromises();

      expect(redis.hashes.get(stateKey)?.status).toBe('open');
      expect(redis.sadd).toHaveBeenCalledWith(sessionSetKey(), TENANT);
    });

    it('caches inbound messages from messages.upsert', async () => {
      await conn.open();
      lastSocket().handlers['messages.upsert']({
        messages: [{ key: { remoteJid: 'jid', id: 'in1' }, message: {} }],
      });
      await flushPromises();

      expect(mockCacheMessage).toHaveBeenCalledWith(
        redis,
        TENANT,
        'jid',
        'in1',
        {},
      );
    });

    it('ignores events from a connection that is no longer active', async () => {
      await conn.open();
      hooks.isActive.mockReturnValue(false);

      lastSocket().handlers['connection.update']({ connection: 'open' });
      await flushPromises();

      expect(redis.sadd).not.toHaveBeenCalled();
    });
  });

  describe('send', () => {
    it('resolves the JID, sends the text and caches the outbound message', async () => {
      await conn.open();

      const result = await conn.send('5511999998888', 'hello');

      expect(lastSocket().sendMessage).toHaveBeenCalledWith(
        '5511999@s.whatsapp.net',
        { text: 'hello' },
      );
      expect(mockCacheMessage).toHaveBeenCalled();
      expect(result).toEqual({ jid: '5511999@s.whatsapp.net' });
    });

    it('rejects when the number has no WhatsApp account', async () => {
      await conn.open();
      lastSocket().onWhatsApp.mockResolvedValueOnce([{ exists: false }]);

      await expect(conn.send('5511000', 'hi')).rejects.toBeInstanceOf(
        BadRequestException,
      );
    });
  });

  describe('disconnect handling', () => {
    it('clears credentials and releases ownership on logout', async () => {
      await conn.open();

      fireClose(lastSocket(), 401);
      await flushPromises();

      // auth keys wiped and ownership released
      expect(redis.keys).toHaveBeenCalledWith(`${redisPrefix()}:auth:t1:*`);
      expect(redis.del).toHaveBeenCalled();
      expect(redis.owners.get(ownerKey)).toBeUndefined();
      expect(redis.hashes.get(stateKey)).toBeUndefined();
    });

    it('stops without reconnecting when the connection is replaced', async () => {
      jest.useFakeTimers();
      await conn.open();

      fireClose(lastSocket(), 440);
      await flushPromises();

      expect(redis.srem).toHaveBeenCalledWith(sessionSetKey(), TENANT);
      expect(redis.owners.get(ownerKey)).toBeUndefined();

      await jest.advanceTimersByTimeAsync(60000);
      expect(mockMakeWASocket).toHaveBeenCalledTimes(1);
    });

    it('schedules a backoff reconnect on a transient disconnect', async () => {
      jest.useFakeTimers();
      await conn.open();

      fireClose(lastSocket(), 500);
      await flushPromises();
      expect(redis.hashes.get(stateKey)?.status).toBe('closed');
      expect(redis.hashes.get(stateKey)?.reconnectAttempts).toBe('1');

      await jest.advanceTimersByTimeAsync(1000);
      expect(hooks.reopen).toHaveBeenCalledTimes(1);
    });

    it('clears credentials once the max reconnect attempts are exceeded', async () => {
      redis.hashes.set(stateKey, { reconnectAttempts: '3' });
      await conn.open();

      fireClose(lastSocket(), 500); // attempt 4 > max (3)
      await flushPromises();

      expect(redis.keys).toHaveBeenCalledWith(`${redisPrefix()}:auth:t1:*`);
      expect(redis.owners.get(ownerKey)).toBeUndefined();
    });
  });

  describe('shutdown', () => {
    it('end() is best-effort and swallows socket errors', async () => {
      await conn.open();
      lastSocket().end.mockImplementationOnce(() => {
        throw new Error('already closed');
      });

      expect(() => conn.end()).not.toThrow();
    });

    it('teardown deregisters the active connection', async () => {
      await conn.open();

      conn.teardown();

      expect(hooks.deregister).toHaveBeenCalledWith(conn);
      expect(lastSocket().end).toHaveBeenCalled();
    });
  });
});
