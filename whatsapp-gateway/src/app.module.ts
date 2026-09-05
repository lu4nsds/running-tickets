import { Module } from '@nestjs/common';
import { ConfigModule } from '@nestjs/config';
import { APP_GUARD } from '@nestjs/core';
import { AppController } from '@src/app.controller';
import { AppService } from '@src/app.service';
import { SessionBusService } from '@src/bus/session-bus.service';
import { GatewayConfig } from '@src/config/gateway.config';
import { ApiKeyGuard } from '@src/guards/api-key.guard';
import { HealthModule } from '@src/modules/health/health.module';
import { instanceProvider } from '@src/providers/instance.provider';
import { RedisModule } from '@src/providers/redis.module';
import { SessionManager } from '@src/session/session-manager.service';
import { TenantConnection } from '@src/session/tenant-connection.service';

@Module({
  imports: [
    ConfigModule.forRoot({ isGlobal: true }),
    RedisModule,
    HealthModule,
  ],
  controllers: [AppController],
  providers: [
    { provide: APP_GUARD, useClass: ApiKeyGuard },
    instanceProvider,
    GatewayConfig,
    SessionBusService,
    TenantConnection,
    SessionManager,
    AppService,
  ],
})
export class AppModule {}
