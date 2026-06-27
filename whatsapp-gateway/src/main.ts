import { ValidationPipe } from '@nestjs/common';
import { NestFactory } from '@nestjs/core';
import { json } from 'express';
import { AppModule } from '@src/app.module';
import { ConsoleFilterAdapter } from '@src/adapters/console-filter.adapter';

ConsoleFilterAdapter.apply();

async function bootstrap(): Promise<void> {
  const app = await NestFactory.create(AppModule);
  // Base64-encoded documents (e.g. ticket PDFs) exceed the default body limit.
  app.use(json({ limit: process.env.PAYLOAD_SIZE_LIMIT ?? '10mb' }));
  app.useGlobalPipes(
    new ValidationPipe({ whitelist: true, forbidNonWhitelisted: true }),
  );
  app.enableShutdownHooks();

  const port = Number.parseInt(process.env.PORT ?? '3000', 10);
  await app.listen(port, '0.0.0.0');
}

void bootstrap();
