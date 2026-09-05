import {
  BadRequestException,
  Inject,
  Injectable,
  InternalServerErrorException,
  Logger,
} from '@nestjs/common';
import type Redis from 'ioredis';
import { GatewayConfig } from '@src/config/gateway.config';
import { REDIS_CLIENT } from '@src/providers/redis.provider';
import { INSTANCE_ID } from '@src/providers/instance.provider';
import { SessionBusService } from '@src/bus/session-bus.service';
import type { BusAction } from '@src/bus/session-bus.service';
import { SessionManager } from '@src/session/session-manager.service';
import { claimOwnership, getOwner } from '@src/states/redis-ownership.state';
import { getSessionState } from '@src/states/redis-session.state';
import { normalizeBrazilPhone } from '@src/utils/phone.util';
import type { GatewayStatus, SendMessageInput } from '@src/types/whatsapp.type';

// Front door for every gateway request. Its only job is cross-instance routing:
// decide whether this instance owns the tenant (act locally) or another one does
// (forward over the bus). All socket lifecycle lives in the SessionManager.
@Injectable()
export class AppService {
  private readonly logger = new Logger(AppService.name);

  constructor(
    @Inject(REDIS_CLIENT) private readonly redis: Redis,
    @Inject(INSTANCE_ID) private readonly instanceId: string,
    private readonly bus: SessionBusService,
    private readonly config: GatewayConfig,
    private readonly manager: SessionManager,
  ) {
    // Register before the bus subscribes so we never miss an inbound request.
    this.bus.registerHandler((action, tenantUuid, payload) => {
      if (action === 'send') {
        return this.sendLocal(tenantUuid, payload as SendMessageInput);
      }
      if (action === 'remove') {
        return this.manager.removeLocal(tenantUuid);
      }
      if (action === 'connect') {
        return this.connectLocal(tenantUuid);
      }
      return Promise.reject(new Error(`Unknown bus action: ${String(action)}`));
    });
  }

  async connect(
    tenantUuid: string,
  ): Promise<{ status: GatewayStatus; qr: string | null }> {
    const owner = await getOwner(this.redis, tenantUuid);

    // Owned by another live instance. Devolver só o status deixaria um tenant
    // parado em `closed`/`error` lá impossível de reconectar daqui: o usuário
    // clicaria em "Conectar" para sempre sem receber QR nenhum. Pedimos à dona
    // que reabra, e é ela quem gera o QR.
    if (owner && owner !== this.instanceId) {
      return this.overBus(owner, 'connect', tenantUuid, null, () =>
        this.connectLocal(tenantUuid),
      );
    }

    return this.connectLocal(tenantUuid);
  }

  async sendMessage(
    tenantUuid: string,
    input: SendMessageInput,
  ): Promise<{ ok: true; phone: string }> {
    const owner = await getOwner(this.redis, tenantUuid);

    if (owner && owner !== this.instanceId) {
      return this.overBus(owner, 'send', tenantUuid, input, () =>
        this.sendLocal(tenantUuid, input),
      );
    }

    if (!owner && !(await this.claim(tenantUuid))) {
      const current = await getOwner(this.redis, tenantUuid);
      if (current && current !== this.instanceId) {
        return this.overBus(current, 'send', tenantUuid, input, () =>
          this.sendLocal(tenantUuid, input),
        );
      }
    }

    return this.sendLocal(tenantUuid, input);
  }

  async getStatus(
    tenantUuid: string,
  ): Promise<{ status: GatewayStatus; qr: string | null }> {
    const state = await getSessionState(this.redis, tenantUuid);
    return { status: state.status, qr: state.qr };
  }

  async removeSession(tenantUuid: string): Promise<void> {
    const owner = await getOwner(this.redis, tenantUuid);

    if (owner && owner !== this.instanceId) {
      await this.overBus(owner, 'remove', tenantUuid, null, () =>
        this.manager.removeLocal(tenantUuid),
      );
      return;
    }

    await this.manager.removeLocal(tenantUuid);
  }

  // --- internals ------------------------------------------------------------

  // Encaminha a operação para a instância dona e, se o RPC falhar, tenta assumir
  // o tenant localmente.
  //
  // Sem esse resgate, uma instância que morreu mas cujo lock ainda não expirou
  // (até `ownershipTtlMs`) engole toda requisição do tenant: o RPC fica pendurado
  // até `rpcTimeoutMs` (45s) e falha — e o timeout HTTP do Laravel é 60s, então o
  // lembrete simplesmente se perde. Se o lock já caiu quando o RPC falha, a dona
  // não existe mais e podemos executar aqui.
  private async overBus<T>(
    ownerId: string,
    action: BusAction,
    tenantUuid: string,
    payload: unknown,
    fallback: () => Promise<T>,
  ): Promise<T> {
    try {
      return await this.bus.request<T>(ownerId, action, tenantUuid, payload);
    } catch (error: unknown) {
      const message = error instanceof Error ? error.message : String(error);

      const current = await getOwner(this.redis, tenantUuid);
      if (current && current !== this.instanceId) {
        // A dona continua viva e registrada: o erro é dela, não de roteamento.
        throw error;
      }

      if (!current && !(await this.claim(tenantUuid))) {
        // Outra instância venceu a corrida pelo lock órfão; que ela assuma.
        throw error;
      }

      this.logger.warn(
        `RPC ${action} to ${ownerId} failed (${message}); tenant is orphaned, handling locally`,
      );

      return fallback();
    }
  }

  // O caminho local de `connect`, também usado quando outra instância nos pede
  // por RPC que reabramos um tenant que possuímos.
  private async connectLocal(
    tenantUuid: string,
  ): Promise<{ status: GatewayStatus; qr: string | null }> {
    const owner = await getOwner(this.redis, tenantUuid);

    // We already own it: reuse the live socket unless there is none.
    if (owner === this.instanceId && this.manager.has(tenantUuid)) {
      return this.getStatus(tenantUuid);
    }

    // Unowned — race to claim it. The loser just reports the winner's state.
    if (!owner && !(await this.claim(tenantUuid))) {
      return this.getStatus(tenantUuid);
    }

    return this.manager.open(tenantUuid);
  }

  // Bus signature: the owner executes the actual send for the tenant it holds.
  private async sendLocal(
    tenantUuid: string,
    input: SendMessageInput,
  ): Promise<{ ok: true; phone: string }> {
    const phone = normalizeBrazilPhone(input.phone);
    const isDocument = !!input.data;
    const message = input.message?.trim();

    if (!phone) {
      throw new BadRequestException('phone is required');
    }

    if (isDocument) {
      if (!input.filename || !input.mimetype) {
        throw new BadRequestException(
          'filename and mimetype are required when sending a document',
        );
      }
    } else if (!message) {
      throw new BadRequestException('message is required');
    }

    let conn = this.manager.getConnection(tenantUuid);
    let state = await getSessionState(this.redis, tenantUuid);

    if (!conn || state.status !== 'open') {
      await this.connect(tenantUuid);
      await this.manager.waitUntilOpen(
        tenantUuid,
        this.config.connectTimeoutMs,
      );
      conn = this.manager.getConnection(tenantUuid);
      state = await getSessionState(this.redis, tenantUuid);
    }

    if (!conn || state.status !== 'open') {
      throw new InternalServerErrorException(
        `WhatsApp session for tenant ${tenantUuid} is not connected. Pair with QR and retry.`,
      );
    }

    const { jid } = await conn.send(
      phone,
      isDocument
        ? {
            data: input.data!,
            mimetype: input.mimetype!,
            filename: input.filename!,
            caption: input.caption ?? message,
          }
        : { text: message! },
    );

    return { ok: true, phone: jid };
  }

  private claim(tenantUuid: string): Promise<boolean> {
    return claimOwnership(
      this.redis,
      tenantUuid,
      this.instanceId,
      this.config.ownershipTtlMs,
    );
  }
}
