import { ServiceUnavailableException } from '@nestjs/common';
import { Test, TestingModule } from '@nestjs/testing';
import type Redis from 'ioredis';
import { IS_PUBLIC_KEY } from '@src/guards/api-key.guard';
import { REDIS_CLIENT } from '@src/providers/redis.provider';
import { HealthController } from '@src/modules/health/health.controller';

describe('HealthController', () => {
  let controller: HealthController;
  let ping: jest.Mock;

  beforeEach(async () => {
    ping = jest.fn().mockResolvedValue('PONG');

    const module: TestingModule = await Test.createTestingModule({
      controllers: [HealthController],
      providers: [
        { provide: REDIS_CLIENT, useValue: { ping } as unknown as Redis },
      ],
    }).compile();

    controller = module.get<HealthController>(HealthController);
  });

  afterEach(() => {
    jest.clearAllMocks();
    jest.useRealTimers();
  });

  it('reports ok when Redis answers the ping', async () => {
    await expect(controller.check()).resolves.toEqual({
      status: 'ok',
      redis: 'ok',
    });
    expect(ping).toHaveBeenCalled();
  });

  // Todo o estado de sessão vive no Redis. Sem ele a instância não atende
  // requisição nenhuma, então precisa sair da rotação em vez de seguir "saudável".
  it('fails the check when Redis is unreachable', async () => {
    ping.mockRejectedValue(new Error('ECONNREFUSED'));

    await expect(controller.check()).rejects.toThrow(
      ServiceUnavailableException,
    );
  });

  it('fails the check when the ping hangs past the timeout', async () => {
    jest.useFakeTimers();
    ping.mockReturnValue(new Promise(() => undefined));

    const assertion = expect(controller.check()).rejects.toThrow(
      ServiceUnavailableException,
    );

    await jest.advanceTimersByTimeAsync(2000);
    await assertion;
  });

  it('stays reachable without an API key', () => {
    const isPublic = Reflect.getMetadata(
      IS_PUBLIC_KEY,
      HealthController,
    ) as boolean;
    expect(isPublic).toBe(true);
  });
});
