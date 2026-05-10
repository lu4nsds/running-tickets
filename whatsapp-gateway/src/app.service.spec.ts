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
import { ConfigService } from '@nestjs/config';
import { AppService } from '@src/app.service';
import { REDIS_CLIENT } from '@src/providers/redis.provider';

const mockRedis = {
  smembers: jest.fn().mockResolvedValue([]),
};

const mockConfigService = {
  get: jest.fn().mockReturnValue('45000'),
};

describe('AppService', () => {
  let service: AppService;

  beforeEach(async () => {
    const module: TestingModule = await Test.createTestingModule({
      providers: [
        AppService,
        { provide: REDIS_CLIENT, useValue: mockRedis },
        { provide: ConfigService, useValue: mockConfigService },
      ],
    }).compile();

    service = module.get<AppService>(AppService);
  });

  afterEach(() => jest.clearAllMocks());

  describe('getStatus', () => {
    it('should return closed status for unknown tenant', () => {
      const result = service.getStatus('unknown-uuid');
      expect(result).toEqual({ status: 'closed', qr: null });
    });
  });
});
