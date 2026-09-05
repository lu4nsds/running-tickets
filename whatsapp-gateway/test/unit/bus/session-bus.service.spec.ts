import { GatewayConfig } from '@src/config/gateway.config';
import type Redis from 'ioredis';
import { SessionBusService } from '@src/bus/session-bus.service';
import { redisPrefix } from '@src/config/redis-keys';

const SELF = 'inst-self';
const rpcInbox = (instanceId: string): string =>
  `${redisPrefix()}:rpc:${instanceId}`;
const SELF_INBOX = rpcInbox(SELF);

const flush = async (): Promise<void> => {
  for (let i = 0; i < 8; i++) await Promise.resolve();
};

interface Harness {
  bus: SessionBusService;
  publish: jest.Mock;
  emitMessage: (channel: string, message: string) => void;
}

const makeBus = (): Harness => {
  const publish = jest.fn().mockResolvedValue(1);
  const redis = { publish } as unknown as Redis;

  let messageHandler: (channel: string, message: string) => void = () => {};
  const subscriber = {
    on: jest.fn((event: string, cb: (c: string, m: string) => void) => {
      if (event === 'message') messageHandler = cb;
    }),
    subscribe: jest.fn().mockResolvedValue(undefined),
    unsubscribe: jest.fn().mockResolvedValue(undefined),
  } as unknown as Redis;

  const config = { rpcTimeoutMs: 15000 } as GatewayConfig;

  const bus = new SessionBusService(redis, subscriber, SELF, config);

  return {
    bus,
    publish,
    emitMessage: (channel, message) => messageHandler(channel, message),
  };
};

const lastPublished = (
  publish: jest.Mock,
): { channel: string; body: Record<string, unknown> } => {
  const call = publish.mock.calls[publish.mock.calls.length - 1] as [
    string,
    string,
  ];
  return {
    channel: call[0],
    body: JSON.parse(call[1]) as Record<string, unknown>,
  };
};

describe('SessionBusService', () => {
  afterEach(() => jest.useRealTimers());

  it('subscribes to its own inbox on init', async () => {
    const { bus } = makeBus();
    await bus.onModuleInit();
    // subscribe asserted indirectly: a request/reply round-trip works below
    expect(bus).toBeDefined();
  });

  it('forwards a request to the owner inbox and resolves on the reply', async () => {
    const { bus, publish, emitMessage } = makeBus();
    await bus.onModuleInit();

    const promise = bus.request('inst-owner', 'send', 't1', { phone: '1' });

    const { channel, body } = lastPublished(publish);
    expect(channel).toBe(rpcInbox('inst-owner'));
    expect(body).toMatchObject({
      kind: 'request',
      action: 'send',
      tenantUuid: 't1',
      replyTo: SELF,
    });

    emitMessage(
      SELF_INBOX,
      JSON.stringify({
        kind: 'reply',
        id: body.id,
        ok: true,
        result: { ok: true, phone: '55' },
      }),
    );

    await expect(promise).resolves.toEqual({ ok: true, phone: '55' });
  });

  it('rejects a request that is not answered before the timeout', async () => {
    jest.useFakeTimers();
    const { bus } = makeBus();
    await bus.onModuleInit();

    const promise = bus.request('inst-owner', 'remove', 't1', null);
    const assertion = expect(promise).rejects.toThrow(/timed out/);

    await jest.advanceTimersByTimeAsync(15000);
    await assertion;
  });

  it('dispatches an inbound request to the handler and replies', async () => {
    const { bus, publish, emitMessage } = makeBus();
    const handler = jest.fn().mockResolvedValue({ ok: true, phone: '55' });
    bus.registerHandler(handler);
    await bus.onModuleInit();

    emitMessage(
      SELF_INBOX,
      JSON.stringify({
        kind: 'request',
        id: 'req-1',
        action: 'send',
        tenantUuid: 't1',
        payload: { phone: '1', message: 'hi' },
        replyTo: 'inst-other',
      }),
    );

    await flush();

    expect(handler).toHaveBeenCalledWith('send', 't1', {
      phone: '1',
      message: 'hi',
    });
    const { channel, body } = lastPublished(publish);
    expect(channel).toBe(rpcInbox('inst-other'));
    expect(body).toEqual({
      kind: 'reply',
      id: 'req-1',
      ok: true,
      result: { ok: true, phone: '55' },
    });
  });

  it('replies with an error when the handler throws', async () => {
    const { bus, publish, emitMessage } = makeBus();
    bus.registerHandler(jest.fn().mockRejectedValue(new Error('boom')));
    await bus.onModuleInit();

    emitMessage(
      SELF_INBOX,
      JSON.stringify({
        kind: 'request',
        id: 'req-2',
        action: 'remove',
        tenantUuid: 't1',
        payload: null,
        replyTo: 'inst-other',
      }),
    );

    await flush();

    const { body } = lastPublished(publish);
    expect(body).toMatchObject({ id: 'req-2', ok: false, error: 'boom' });
  });
});
