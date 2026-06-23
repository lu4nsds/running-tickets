import {
  BadRequestException,
  Inject,
  Injectable,
  InternalServerErrorException,
} from '@nestjs/common';
import type Redis from 'ioredis';
import { GatewayConfig } from '@src/config/gateway.config';
import { REDIS_CLIENT } from '@src/providers/redis.provider';
import { INSTANCE_ID } from '@src/providers/instance.provider';
import { SessionBusService } from '@src/bus/session-bus.service';
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
      return Promise.reject(new Error(`Unknown bus action: ${String(action)}`));
    });
  }

  async connect(
    tenantUuid: string,
  ): Promise<{ status: GatewayStatus; qr: string | null }> {
    const owner = await getOwner(this.redis, tenantUuid);

    // Owned by another live instance — it is already (re)connecting there.
    if (owner && owner !== this.instanceId) {
      return this.getStatus(tenantUuid);
    }

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

  async sendMessage(
    tenantUuid: string,
    input: SendMessageInput,
  ): Promise<{ ok: true; phone: string }> {
    const owner = await getOwner(this.redis, tenantUuid);

    if (owner && owner !== this.instanceId) {
      return this.bus.request(owner, 'send', tenantUuid, input);
    }

    if (!owner && !(await this.claim(tenantUuid))) {
      const current = await getOwner(this.redis, tenantUuid);
      if (current && current !== this.instanceId) {
        return this.bus.request(current, 'send', tenantUuid, input);
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
      await this.bus.request(owner, 'remove', tenantUuid, null);
      return;
    }

    await this.manager.removeLocal(tenantUuid);
  }

  // --- internals ------------------------------------------------------------

  // Bus signature: the owner executes the actual send for the tenant it holds.
  private async sendLocal(
    tenantUuid: string,
    input: SendMessageInput,
  ): Promise<{ ok: true; phone: string }> {
    const phone = normalizeBrazilPhone(input.phone);
    const message = input.message?.trim();

    if (!phone || !message) {
      throw new BadRequestException('phone and message are required');
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

    const { jid } = await conn.send(phone, message);
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
