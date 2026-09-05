import { Logger, ValidationPipe } from '@nestjs/common';
import { NestFactory } from '@nestjs/core';
import { json } from 'express';
import { AppModule } from '@src/app.module';
import { ConsoleFilterAdapter } from '@src/adapters/console-filter.adapter';

ConsoleFilterAdapter.apply();

// Rede de segurança: uma promise não tratada em qualquer lugar não pode derrubar
// o gateway inteiro (todos os tenants) — o padrão do Node é encerrar o processo.
// Cada caminho conhecido já trata o próprio erro; isto é a última barreira, e um
// log aqui indica um `fireAndForget` faltando.
function installProcessGuards(): void {
  const logger = new Logger('Process');

  process.on('unhandledRejection', (reason: unknown) => {
    const message = reason instanceof Error ? reason.stack : String(reason);
    logger.error(`Unhandled promise rejection: ${message}`);
  });

  process.on('uncaughtException', (error: Error) => {
    logger.error(`Uncaught exception: ${error.stack ?? error.message}`);
  });
}

async function bootstrap(): Promise<void> {
  installProcessGuards();

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
