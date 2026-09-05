import { createHash, timingSafeEqual } from 'node:crypto';
import {
  CanActivate,
  ExecutionContext,
  Injectable,
  SetMetadata,
  UnauthorizedException,
} from '@nestjs/common';
import { Reflector } from '@nestjs/core';
import { GatewayConfig } from '@src/config/gateway.config';

export const IS_PUBLIC_KEY = 'isPublic';
export const Public = () => SetMetadata(IS_PUBLIC_KEY, true);

// Compara em tempo constante. Os digests SHA-256 têm sempre 32 bytes, então
// `timingSafeEqual` nunca lança por tamanho diferente e o comprimento da chave
// recebida não vaza pelo tempo de resposta.
function matches(received: string, expected: string): boolean {
  const digest = (value: string): Buffer =>
    createHash('sha256').update(value).digest();

  return timingSafeEqual(digest(received), digest(expected));
}

@Injectable()
export class ApiKeyGuard implements CanActivate {
  constructor(
    private readonly config: GatewayConfig,
    private readonly reflector: Reflector,
  ) {}

  // Fail-closed: a chave é obrigatória e validada no boot pelo GatewayConfig, então
  // aqui nunca existe o caso "sem chave configurada, libera tudo". A porta do
  // gateway é publicada, e liberar geral significaria enviar mensagem como
  // qualquer tenant e apagar qualquer sessão sem autenticação alguma.
  canActivate(context: ExecutionContext): boolean {
    const isPublic = this.reflector.getAllAndOverride<boolean>(IS_PUBLIC_KEY, [
      context.getHandler(),
      context.getClass(),
    ]);

    if (isPublic) return true;

    const request = context
      .switchToHttp()
      .getRequest<{ headers: Record<string, string | string[] | undefined> }>();

    const header = request.headers['x-api-key'];
    const auth = request.headers.authorization;

    const receivedKey = Array.isArray(header) ? header[0] : header;
    const bearer =
      typeof auth === 'string' && auth.startsWith('Bearer ')
        ? auth.slice(7)
        : undefined;

    const accepted = [receivedKey, bearer].some(
      (candidate) => !!candidate && matches(candidate, this.config.apiKey),
    );

    if (accepted) return true;

    throw new UnauthorizedException('Invalid API key');
  }
}
