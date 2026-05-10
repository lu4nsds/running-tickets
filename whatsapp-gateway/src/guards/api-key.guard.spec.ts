import { ExecutionContext, UnauthorizedException } from '@nestjs/common';
import { ConfigService } from '@nestjs/config';
import { Reflector } from '@nestjs/core';
import { ApiKeyGuard, IS_PUBLIC_KEY } from '@src/guards/api-key.guard';

function buildContext(headers: Record<string, string>): ExecutionContext {
  return {
    getHandler: jest.fn(),
    getClass: jest.fn(),
    switchToHttp: () => ({
      getRequest: () => ({ headers }),
    }),
  } as unknown as ExecutionContext;
}

describe('ApiKeyGuard', () => {
  let guard: ApiKeyGuard;
  let configService: jest.Mocked<ConfigService>;
  let reflector: jest.Mocked<Reflector>;

  beforeEach(() => {
    configService = { get: jest.fn() } as unknown as jest.Mocked<ConfigService>;
    reflector = {
      getAllAndOverride: jest.fn(),
    } as unknown as jest.Mocked<Reflector>;
    guard = new ApiKeyGuard(configService, reflector);
  });

  it('should allow request when route is marked @Public()', () => {
    reflector.getAllAndOverride.mockReturnValue(true);
    const ctx = buildContext({});
    expect(guard.canActivate(ctx)).toBe(true);
  });

  it('should allow request when no API key is configured', () => {
    reflector.getAllAndOverride.mockReturnValue(false);
    configService.get.mockReturnValue(undefined);
    const ctx = buildContext({});
    expect(guard.canActivate(ctx)).toBe(true);
  });

  it('should allow request with correct key in x-api-key header', () => {
    reflector.getAllAndOverride.mockReturnValue(false);
    configService.get.mockReturnValue('secret-key');
    const ctx = buildContext({ 'x-api-key': 'secret-key' });
    expect(guard.canActivate(ctx)).toBe(true);
  });

  it('should allow request with correct Bearer token', () => {
    reflector.getAllAndOverride.mockReturnValue(false);
    configService.get.mockReturnValue('secret-key');
    const ctx = buildContext({ authorization: 'Bearer secret-key' });
    expect(guard.canActivate(ctx)).toBe(true);
  });

  it('should reject request with wrong key', () => {
    reflector.getAllAndOverride.mockReturnValue(false);
    configService.get.mockReturnValue('secret-key');
    const ctx = buildContext({ 'x-api-key': 'wrong-key' });
    expect(() => guard.canActivate(ctx)).toThrow(UnauthorizedException);
  });

  it('should export IS_PUBLIC_KEY constant', () => {
    expect(IS_PUBLIC_KEY).toBe('isPublic');
  });
});
