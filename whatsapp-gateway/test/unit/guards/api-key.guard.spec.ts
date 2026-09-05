import { ExecutionContext, UnauthorizedException } from '@nestjs/common';
import { Reflector } from '@nestjs/core';
import { GatewayConfig } from '@src/config/gateway.config';
import { ApiKeyGuard, IS_PUBLIC_KEY } from '@src/guards/api-key.guard';

const API_KEY = 'secret-key';

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
  let reflector: jest.Mocked<Reflector>;

  beforeEach(() => {
    reflector = {
      getAllAndOverride: jest.fn().mockReturnValue(false),
    } as unknown as jest.Mocked<Reflector>;
    guard = new ApiKeyGuard({ apiKey: API_KEY } as GatewayConfig, reflector);
  });

  it('should allow request when route is marked @Public()', () => {
    reflector.getAllAndOverride.mockReturnValue(true);
    expect(guard.canActivate(buildContext({}))).toBe(true);
  });

  it('should allow request with correct key in x-api-key header', () => {
    expect(guard.canActivate(buildContext({ 'x-api-key': API_KEY }))).toBe(
      true,
    );
  });

  it('should allow request with correct Bearer token', () => {
    const ctx = buildContext({ authorization: `Bearer ${API_KEY}` });
    expect(guard.canActivate(ctx)).toBe(true);
  });

  it('should reject request with wrong key', () => {
    const ctx = buildContext({ 'x-api-key': 'wrong-key' });
    expect(() => guard.canActivate(ctx)).toThrow(UnauthorizedException);
  });

  // Fail-closed. Antes, uma chave não configurada liberava todas as rotas — um
  // deploy com env faltando expunha enviar mensagem e apagar sessão de qualquer
  // tenant, sem autenticação. Hoje a chave é obrigatória (GatewayConfig falha no
  // boot) e nenhuma requisição passa sem ela.
  it('should reject a request that carries no credentials at all', () => {
    expect(() => guard.canActivate(buildContext({}))).toThrow(
      UnauthorizedException,
    );
  });

  it('should reject a key that is a prefix of the expected one', () => {
    const ctx = buildContext({ 'x-api-key': API_KEY.slice(0, 3) });
    expect(() => guard.canActivate(ctx)).toThrow(UnauthorizedException);
  });

  it('should reject an Authorization header without the Bearer scheme', () => {
    const ctx = buildContext({ authorization: API_KEY });
    expect(() => guard.canActivate(ctx)).toThrow(UnauthorizedException);
  });

  it('should export IS_PUBLIC_KEY constant', () => {
    expect(IS_PUBLIC_KEY).toBe('isPublic');
  });
});
