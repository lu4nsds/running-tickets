interface MockSocket {
  config: Record<string, unknown>;
  handlers: Record<string, (arg: unknown) => void>;
  ev: { on: jest.Mock; removeAllListeners: jest.Mock };
  end: jest.Mock;
  logout: jest.Mock;
  onWhatsApp: jest.Mock;
  sendMessage: jest.Mock;
}

jest.mock('@src/utils/baileys.import', () => {
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
      logout: jest.fn().mockResolvedValue(undefined),
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
    importBaileys: jest.fn().mockResolvedValue({
      default: make,
      __sockets: sockets,
      fetchLatestBaileysVersion: jest
        .fn()
        .mockResolvedValue({ version: [2, 3, 0] }),
      DisconnectReason: { loggedOut: 401, connectionReplaced: 440 },
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

// Resolve como a função real (async): os callbacks de evento passam o retorno
// para `fireAndForget`, então um mock síncrono não representaria o caminho real.
const mockCacheMessage = jest.fn().mockResolvedValue(undefined);
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
import { GatewayConfig } from '@src/config/gateway.config';
import { redisPrefix, sessionSetKey } from '@src/config/redis-keys';
import { TenantConnection } from '@src/session/tenant-connection.service';
import type { TenantConnectionHooks } from '@src/session/tenant-connection.service';
import { useRedisAuthState } from '@src/states/redis-auth.state';
import { fakeScanSupport } from '@test/helpers/fake-redis-scan';
import { importBaileys } from '@src/utils/baileys.import';

let mockMakeWASocket: jest.Mock;
let mockSockets: MockSocket[];
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

  beforeEach(async () => {
    const mod = (await importBaileys()) as unknown as {
      default: jest.Mock;
      __sockets: MockSocket[];
    };
    mockMakeWASocket = mod.default;
    mockSockets = mod.__sockets;

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
      saveCreds: jest.fn().mockResolvedValue(undefined),
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

    it('builds the auth state fenced by this instance identity', async () => {
      await conn.open();

      expect(mockUseRedisAuthState).toHaveBeenCalledWith(
        redis,
        TENANT,
        INSTANCE,
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

      const result = await conn.send('5511999998888', { text: 'hello' });

      expect(lastSocket().sendMessage).toHaveBeenCalledWith(
        '5511999@s.whatsapp.net',
        { text: 'hello' },
      );
      expect(mockCacheMessage).toHaveBeenCalled();
      expect(result).toEqual({ jid: '5511999@s.whatsapp.net' });
    });

    it('resolves the JID, sends a document and caches the outbound message', async () => {
      await conn.open();

      const result = await conn.send('5511999998888', {
        data: Buffer.from('pdf-bytes').toString('base64'),
        mimetype: 'application/pdf',
        filename: 'ingresso.pdf',
        caption: 'Seu ingresso',
      });

      expect(lastSocket().sendMessage).toHaveBeenCalledWith(
        '5511999@s.whatsapp.net',
        {
          document: Buffer.from('pdf-bytes'),
          mimetype: 'application/pdf',
          fileName: 'ingresso.pdf',
          caption: 'Seu ingresso',
        },
      );
      expect(mockCacheMessage).toHaveBeenCalled();
      expect(result).toEqual({ jid: '5511999@s.whatsapp.net' });
    });

    it('rejects when the number has no WhatsApp account', async () => {
      await conn.open();
      lastSocket().onWhatsApp.mockResolvedValueOnce([{ exists: false }]);

      await expect(conn.send('5511000', { text: 'hi' })).rejects.toBeInstanceOf(
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
      expect(redis.scans.map((scan) => scan.match)).toEqual([
        `${redisPrefix()}:auth:t1:*`,
      ]);
      expect(redis.unlinked).toHaveLength(1);
      expect(redis.keys).not.toHaveBeenCalled();
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

    // Uma queda transiente longa não pode custar um repareamento por QR: o
    // gateway entra em modo degradado (backoff no teto) e segue tentando com as
    // credenciais intactas.
    it('keeps credentials and keeps retrying past the max reconnect attempts', async () => {
      jest.useFakeTimers();
      redis.hashes.set(stateKey, { reconnectAttempts: '3' });
      await conn.open();

      fireClose(lastSocket(), 500); // attempt 4 > max (3)
      await flushPromises();

      // credenciais preservadas e lock mantido
      expect(redis.unlinked).toEqual([]);
      expect(redis.owners.get(ownerKey)).toBe(INSTANCE);
      expect(redis.hashes.get(stateKey)?.status).toBe('closed');
      expect(redis.hashes.get(stateKey)?.reconnectAttempts).toBe('4');

      // e a próxima tentativa acontece no teto do backoff
      await jest.advanceTimersByTimeAsync(60000);
      expect(hooks.reopen).toHaveBeenCalledTimes(1);
    });

    it('caps the backoff at the configured max delay when degraded', async () => {
      jest.useFakeTimers();
      redis.hashes.set(stateKey, { reconnectAttempts: '20' });
      await conn.open();

      fireClose(lastSocket(), 500);
      await flushPromises();

      // 2^20 * 1000ms estouraria em muito o teto; o delay tem de ser exatamente
      // WHATSAPP_RECONNECT_MAX_DELAY_MS
      await jest.advanceTimersByTimeAsync(59999);
      expect(hooks.reopen).not.toHaveBeenCalled();
      await jest.advanceTimersByTimeAsync(1);
      expect(hooks.reopen).toHaveBeenCalledTimes(1);
    });

    it('only wipes credentials when WhatsApp itself logged the device out', async () => {
      await conn.open();

      // qualquer motivo transiente não pode apagar nada
      for (const statusCode of [408, 428, 500, 515]) {
        fireClose(lastSocket(), statusCode);
        await flushPromises();
        expect(redis.unlinked).toEqual([]);
      }
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
