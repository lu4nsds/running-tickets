jest.mock('@whiskeysockets/baileys', () => ({
  default: jest.fn(),
  DisconnectReason: {
    loggedOut: 'loggedOut',
    connectionClosed: 'connectionClosed',
    restartRequired: 'restartRequired',
  },
  BufferJSON: { replacer: jest.fn(), reviver: jest.fn() },
  proto: {},
}));

import { Test, TestingModule } from '@nestjs/testing';
import { AppController } from '@src/app.controller';
import { AppService } from '@src/app.service';

const mockAppService = {
  getStatus: jest.fn().mockReturnValue({ status: 'closed', qr: null }),
};

describe('AppController', () => {
  let controller: AppController;

  beforeEach(async () => {
    const module: TestingModule = await Test.createTestingModule({
      controllers: [AppController],
      providers: [{ provide: AppService, useValue: mockAppService }],
    }).compile();

    controller = module.get<AppController>(AppController);
  });

  afterEach(() => jest.clearAllMocks());

  describe('status', () => {
    it('should return session status for a tenant', () => {
      const result = controller.status('test-uuid');
      expect(result).toEqual({ status: 'closed', qr: null });
      expect(mockAppService.getStatus).toHaveBeenCalledWith('test-uuid');
    });
  });

  describe('qr', () => {
    it('should return qr code for a tenant', () => {
      const result = controller.qr('test-uuid');
      expect(result).toEqual({ qr: null });
    });
  });
});
