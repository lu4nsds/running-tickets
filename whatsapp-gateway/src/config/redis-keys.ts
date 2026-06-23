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
