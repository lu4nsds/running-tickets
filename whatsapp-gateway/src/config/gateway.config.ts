import { Injectable } from '@nestjs/common';
import { ConfigService } from '@nestjs/config';
import { redisPrefix } from '@src/config/redis-keys';

// Typed, validated-once view over the WhatsApp gateway environment. Reading the
// raw `ConfigService` (string in, manual `Number(...)` out) is centralised here
// so the rest of the codebase depends on plain numbers instead of env strings.
@Injectable()
export class GatewayConfig {
  readonly connectTimeoutMs: number;
  readonly reconnectMaxAttempts: number;
  readonly reconnectBaseDelayMs: number;
  readonly reconnectMaxDelayMs: number;
  readonly ownershipTtlMs: number;
  readonly heartbeatIntervalMs: number;
  readonly reconcileIntervalMs: number;
  readonly deviceName: string;
  readonly baileysLogLevel?: string;

  constructor(config: ConfigService) {
    this.connectTimeoutMs = this.number(config, 'WHATSAPP_CONNECT_TIMEOUT_MS');
    this.reconnectMaxAttempts = this.number(
      config,
      'WHATSAPP_RECONNECT_MAX_ATTEMPTS',
    );
    this.reconnectBaseDelayMs = this.number(
      config,
      'WHATSAPP_RECONNECT_BASE_DELAY_MS',
    );
    this.reconnectMaxDelayMs = this.number(
      config,
      'WHATSAPP_RECONNECT_MAX_DELAY_MS',
    );
    this.ownershipTtlMs = this.number(config, 'WHATSAPP_OWNERSHIP_TTL_MS');
    this.heartbeatIntervalMs = this.number(
      config,
      'WHATSAPP_HEARTBEAT_INTERVAL_MS',
    );
    this.reconcileIntervalMs = this.number(
      config,
      'WHATSAPP_RECONCILE_INTERVAL_MS',
    );
    this.deviceName = this.requiredString(config, 'WHATSAPP_DEVICE_NAME');
    this.baileysLogLevel = config
      .get<string>('WHATSAPP_BAILEYS_LOG_LEVEL')
      ?.trim();

    // GatewayConfig é instanciado eagerly pela DI, então validar o prefixo aqui
    // faz o boot falhar de imediato se REDIS_PREFIX estiver ausente (em vez de só
    // quebrar mais tarde, quando a primeira chave Redis for construída).
    redisPrefix();
  }

  private number(config: ConfigService, key: string): number {
    return Number(config.get<string>(key));
  }

  private requiredString(config: ConfigService, key: string): string {
    const value = config.get<string>(key)?.trim();
    if (!value) {
      throw new Error(`Missing required env var: ${key}`);
    }
    return value;
  }
}
