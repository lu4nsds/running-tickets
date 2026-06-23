import { randomUUID } from 'node:crypto';
import {
  Inject,
  Injectable,
  Logger,
  OnModuleDestroy,
  OnModuleInit,
} from '@nestjs/common';
import { ConfigService } from '@nestjs/config';
import type Redis from 'ioredis';
import { redisPrefix } from '@src/config/redis-keys';
import { REDIS_CLIENT, REDIS_SUBSCRIBER } from '@src/providers/redis.provider';
import { INSTANCE_ID } from '@src/providers/instance.provider';

export type BusAction = 'send' | 'remove';

export type BusHandler = (
  action: BusAction,
  tenantUuid: string,
  payload: unknown,
) => Promise<unknown>;

interface BusRequest {
  kind: 'request';
  id: string;
  action: BusAction;
  tenantUuid: string;
  payload: unknown;
  replyTo: string;
}

interface BusReply {
  kind: 'reply';
  id: string;
  ok: boolean;
  result?: unknown;
  error?: string;
}

interface Pending {
  resolve: (value: unknown) => void;
  reject: (reason: Error) => void;
  timer: NodeJS.Timeout;
}

function inboxChannel(instanceId: string): string {
  return `${redisPrefix()}:rpc:${instanceId}`;
}

// Request/reply RPC over Redis pub/sub. Lets any instance forward a socket
// operation (send/remove) to whichever instance currently owns the tenant,
// without needing to address instances directly — Redis is the broker.
@Injectable()
export class SessionBusService implements OnModuleInit, OnModuleDestroy {
  private readonly logger = new Logger(SessionBusService.name);
  private readonly pending = new Map<string, Pending>();
  private readonly timeoutMs: number;
  private handler: BusHandler | null = null;

  constructor(
    @Inject(REDIS_CLIENT) private readonly redis: Redis,
    @Inject(REDIS_SUBSCRIBER) private readonly subscriber: Redis,
    @Inject(INSTANCE_ID) private readonly instanceId: string,
    config: ConfigService,
  ) {
    this.timeoutMs = Number(config.get<string>('WHATSAPP_RPC_TIMEOUT_MS'));
  }

  async onModuleInit(): Promise<void> {
    this.subscriber.on('message', (channel, message) => {
      if (channel !== inboxChannel(this.instanceId)) return;
      void this.onMessage(message);
    });
    await this.subscriber.subscribe(inboxChannel(this.instanceId));
  }

  async onModuleDestroy(): Promise<void> {
    await this.subscriber.unsubscribe(inboxChannel(this.instanceId));
    for (const { reject, timer } of this.pending.values()) {
      clearTimeout(timer);
      reject(new Error('Session bus shutting down'));
    }
    this.pending.clear();
  }

  // AppService registers the function that actually performs the operation when
  // a request lands on the owning instance.
  registerHandler(handler: BusHandler): void {
    this.handler = handler;
  }

  // Forward an operation to the owning instance and await its reply. Rejects on
  // timeout so callers (e.g. send) can surface a best-effort failure.
  request<T>(
    ownerId: string,
    action: BusAction,
    tenantUuid: string,
    payload: unknown,
  ): Promise<T> {
    const id = randomUUID();
    const message: BusRequest = {
      kind: 'request',
      id,
      action,
      tenantUuid,
      payload,
      replyTo: this.instanceId,
    };

    return new Promise<T>((resolve, reject) => {
      const timer = setTimeout(() => {
        this.pending.delete(id);
        reject(
          new Error(
            `RPC ${action} for tenant ${tenantUuid} to ${ownerId} timed out`,
          ),
        );
      }, this.timeoutMs);

      this.pending.set(id, {
        resolve: resolve as (value: unknown) => void,
        reject,
        timer,
      });

      void this.redis.publish(inboxChannel(ownerId), JSON.stringify(message));
    });
  }

  private async onMessage(raw: string): Promise<void> {
    let message: BusRequest | BusReply;
    try {
      message = JSON.parse(raw) as BusRequest | BusReply;
    } catch {
      this.logger.warn('Discarding malformed bus message');
      return;
    }

    if (message.kind === 'reply') {
      this.resolveReply(message);
      return;
    }

    await this.handleRequest(message);
  }

  private resolveReply(reply: BusReply): void {
    const pending = this.pending.get(reply.id);
    if (!pending) return;

    clearTimeout(pending.timer);
    this.pending.delete(reply.id);

    if (reply.ok) {
      pending.resolve(reply.result);
    } else {
      pending.reject(new Error(reply.error ?? 'RPC failed'));
    }
  }

  private async handleRequest(request: BusRequest): Promise<void> {
    const reply: BusReply = { kind: 'reply', id: request.id, ok: true };

    if (!this.handler) {
      reply.ok = false;
      reply.error = 'No handler registered';
    } else {
      try {
        reply.result = await this.handler(
          request.action,
          request.tenantUuid,
          request.payload,
        );
      } catch (error) {
        reply.ok = false;
        reply.error = error instanceof Error ? error.message : String(error);
      }
    }

    await this.redis.publish(
      inboxChannel(request.replyTo),
      JSON.stringify(reply),
    );
  }
}
