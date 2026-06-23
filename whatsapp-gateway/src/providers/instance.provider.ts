import { hostname } from 'node:os';
import { randomUUID } from 'node:crypto';
import { ConfigService } from '@nestjs/config';

export const INSTANCE_ID = 'INSTANCE_ID';

// A stable, per-process identity used to claim tenant ownership and to address
// this instance's RPC inbox over the session bus. Can be overridden via the
// INSTANCE_ID env var (e.g. a stable pod name); otherwise it is derived from the
// hostname plus a random suffix so two replicas never collide.
export const instanceProvider = {
  provide: INSTANCE_ID,
  inject: [ConfigService],
  useFactory: (config: ConfigService): string =>
    config.get<string>('INSTANCE_ID')?.trim() ||
    `${hostname()}:${randomUUID()}`,
};
