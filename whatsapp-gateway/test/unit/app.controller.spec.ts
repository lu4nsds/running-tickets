jest.mock('@src/utils/baileys.import', () => ({
  importBaileys: jest.fn().mockResolvedValue({
    default: jest.fn(),
    DisconnectReason: {
      loggedOut: 'loggedOut',
      connectionClosed: 'connectionClosed',
      restartRequired: 'restartRequired',
    },
    BufferJSON: { replacer: jest.fn(), reviver: jest.fn() },
    proto: {},
  }),
}));

import { INestApplication, ValidationPipe } from '@nestjs/common';
import { Test, TestingModule } from '@nestjs/testing';
import request from 'supertest';
import type { App } from 'supertest/types';
import { AppController } from '@src/app.controller';
import { AppService } from '@src/app.service';

const TENANT = '3f2504e0-4f89-41d3-9a0c-0305e82c3301';

const mockAppService = {
  connect: jest.fn().mockResolvedValue({ status: 'connecting', qr: null }),
  getStatus: jest.fn().mockResolvedValue({ status: 'closed', qr: null }),
  removeSession: jest.fn().mockResolvedValue(undefined),
  sendMessage: jest.fn().mockResolvedValue({ ok: true, phone: '55119@s' }),
};

describe('AppController', () => {
  let controller: AppController;
  let app: INestApplication<App>;

  beforeEach(async () => {
    const module: TestingModule = await Test.createTestingModule({
      controllers: [AppController],
      providers: [{ provide: AppService, useValue: mockAppService }],
    }).compile();

    controller = module.get<AppController>(AppController);
    app = module.createNestApplication();
    app.useGlobalPipes(
      new ValidationPipe({ whitelist: true, forbidNonWhitelisted: true }),
    );
    await app.init();
  });

  afterEach(async () => {
    jest.clearAllMocks();
    await app.close();
  });

  describe('status', () => {
    it('should return session status for a tenant', async () => {
      const result = await controller.status(TENANT);
      expect(result).toEqual({ status: 'closed', qr: null });
      expect(mockAppService.getStatus).toHaveBeenCalledWith(TENANT);
    });
  });

  describe('qr', () => {
    it('should return qr code for a tenant', async () => {
      const result = await controller.qr(TENANT);
      expect(result).toEqual({ qr: null });
    });
  });

  // O tenantUuid vira chave e padrão glob no Redis. Um valor com metacaractere
  // faria a limpeza casar com as chaves de outros tenants, então o pipe tem de
  // barrar tudo que não seja UUID antes de chegar ao service.
  describe('tenant uuid validation', () => {
    const server = (): App => app.getHttpServer();

    it('accepts a well-formed uuid', async () => {
      await request(server())
        .get(`/tenants/${TENANT}/session/status`)
        .expect(200);

      expect(mockAppService.getStatus).toHaveBeenCalledWith(TENANT);
    });

    it.each([
      ['glob wildcard', '%2A'],
      ['glob range', '%5Ba-z%5D'],
      ['single-char glob', '%3F'],
      ['plain string', 'test-uuid'],
    ])('rejects %s and never reaches the service', async (_label, raw) => {
      await request(server()).delete(`/tenants/${raw}/session`).expect(400);

      expect(mockAppService.removeSession).not.toHaveBeenCalled();
    });

    it('rejects a non-uuid on every route that takes a tenant', async () => {
      await request(server()).post('/tenants/%2A/session/connect').expect(400);
      await request(server()).get('/tenants/%2A/session/status').expect(400);
      await request(server()).get('/tenants/%2A/session/qr').expect(400);
      await request(server())
        .post('/tenants/%2A/messages/send')
        .send({ phone: '11999999999', message: 'oi' })
        .expect(400);

      expect(mockAppService.connect).not.toHaveBeenCalled();
      expect(mockAppService.getStatus).not.toHaveBeenCalled();
      expect(mockAppService.sendMessage).not.toHaveBeenCalled();
    });
  });
});
