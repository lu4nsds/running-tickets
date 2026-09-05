// Base namespace de toda chave/canal Redis do gateway. Obrigatório via REDIS_PREFIX,
// SEM separador final — o ':' é adicionado pelos builders. Sem default: o serviço é
// agnóstico a quem o usa, então um prefixo ausente é erro de configuração.
export function redisPrefix(): string {
  const prefix = process.env.REDIS_PREFIX;
  if (!prefix) {
    throw new Error('REDIS_PREFIX environment variable is required');
  }
  return prefix;
}

// Único caso usado como valor direto (Redis SET com todas as sessões ativas).
export function sessionSetKey(): string {
  return `${redisPrefix()}:sessions`;
}

const UUID_PATTERN =
  /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i;

// Invariante de segurança: o tenantUuid é interpolado em padrões glob consumidos
// por SCAN/KEYS, então um valor com metacaractere (`*`, `?`, `[`) faria o padrão
// casar com as chaves de OUTROS tenants — um `DELETE /tenants/*/session` apagaria
// as credenciais Baileys de todo mundo. Um UUID não tem como conter esses
// caracteres, então exigir o formato fecha a classe inteira de problema.
//
// Vale para os construtores de *padrão*; chaves exatas não têm raio de alcance
// além do próprio tenant. Mesmo assim o `ParseUUIDPipe` do controller barra tudo
// na porta de entrada — isto aqui é a segunda camada, para chamadas internas
// (bus RPC, reconcile) que não passam pelo pipe.
export function assertTenantUuid(tenantUuid: string): string {
  if (!UUID_PATTERN.test(tenantUuid)) {
    throw new Error(`Invalid tenant uuid: ${tenantUuid}`);
  }
  return tenantUuid;
}
