import { Module } from '@nestjs/common';
import { ConfigModule } from '@nestjs/config';
import { APP_GUARD } from '@nestjs/core';
import { AppController } from '@src/app.controller';
import { AppService } from '@src/app.service';
import { ApiKeyGuard } from '@src/guards/api-key.guard';
import { HealthModule } from '@src/modules/health/health.module';
import { redisProvider } from '@src/providers/redis.provider';

@Module({
  imports: [ConfigModule.forRoot({ isGlobal: true }), HealthModule],
  controllers: [AppController],
  providers: [
    { provide: APP_GUARD, useClass: ApiKeyGuard },
    redisProvider,
    AppService,
  ],
})
export class AppModule {}
