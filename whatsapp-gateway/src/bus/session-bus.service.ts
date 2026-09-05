import { randomUUID } from 'node:crypto';
import {
  Inject,
  Injectable,
  Logger,
  OnModuleDestroy,
  OnModuleInit,
} from '@nestjs/common';
import type Redis from 'ioredis';
import { GatewayConfig } from '@src/config/gateway.config';
import { redisPrefix } from '@src/config/redis-keys';
import { REDIS_CLIENT, REDIS_SUBSCRIBER } from '@src/providers/redis.provider';
import { INSTANCE_ID } from '@src/providers/instance.provider';
import { fireAndForget } from '@src/utils/fire-and-forget.util';

export type BusAction = 'send' | 'remove' | 'connect';

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
    config: GatewayConfig,
  ) {
    // Via GatewayConfig (e não ConfigService cru) para que uma env ausente falhe
    // no boot em vez de virar NaN — `setTimeout(fn, NaN)` dispara em ~1ms e faria
    // todo RPC entre instâncias falhar instantaneamente.
    this.timeoutMs = config.rpcTimeoutMs;
  }

  async onModuleInit(): Promise<void> {
    this.subscriber.on('message', (channel, message) => {
      if (channel !== inboxChannel(this.instanceId)) return;
      fireAndForget(
        this.onMessage(message),
        this.logger,
        'Failed to process bus message',
      );
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

      // Uma falha no publish não pode escapar como unhandled rejection: resolve-se
      // rejeitando o pending imediatamente, em vez de deixar o chamador esperando
      // o timeout inteiro por um request que nunca saiu.
      this.redis
        .publish(inboxChannel(ownerId), JSON.stringify(message))
        .catch((error: unknown) => {
          const pendingRequest = this.pending.get(id);
          if (!pendingRequest) return;

          clearTimeout(pendingRequest.timer);
          this.pending.delete(id);

          const detail = error instanceof Error ? error.message : String(error);
          pendingRequest.reject(
            new Error(
              `RPC ${action} for tenant ${tenantUuid} to ${ownerId} could not be published: ${detail}`,
            ),
          );
        });
    });
  }

  // Entrypoint do listener de pub/sub. Nunca lança: é invocado por um event
  // handler, então uma rejeição escaparia como unhandled e mataria o processo.
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

    try {
      await this.redis.publish(
        inboxChannel(request.replyTo),
        JSON.stringify(reply),
      );
    } catch (error: unknown) {
      // O trabalho já foi executado; só a resposta se perdeu. O chamador cai no
      // timeout do RPC — melhor que derrubar o processo.
      const message = error instanceof Error ? error.message : String(error);
      this.logger.error(
        `Failed to publish reply for ${request.action} (tenant ${request.tenantUuid}): ${message}`,
      );
    }
  }
}
