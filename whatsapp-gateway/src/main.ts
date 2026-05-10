import { NestFactory } from '@nestjs/core';
import { AppModule } from './app.module';
import { AppService } from './app.service';

async function bootstrap() {
  const app = await NestFactory.create(AppModule, { logger: ['log', 'warn', 'error'] });

  const port = process.env.PORT ?? 3000;
  await app.listen(port);
  console.log(`WhatsApp Gateway running on port ${port}`);

  // Reconecta todas as sessões persistidas ao iniciar
  const service = app.get(AppService);
  await service.reconnectAll();
}

bootstrap();
