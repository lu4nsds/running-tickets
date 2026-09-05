import type Redis from 'ioredis';

const SCAN_BATCH = 200;

// `KEYS` percorre o keyspace inteiro numa única operação e bloqueia o servidor —
// e este Redis é compartilhado com o cache, as filas e as sessões do Laravel.
// `SCAN` faz o mesmo trabalho em fatias, cedendo o event loop entre elas.
//
// A remoção usa `UNLINK` (libera a memória numa thread de fundo) em pipeline, de
// modo que apagar as chaves de um tenant nunca segura o Redis dos outros.
export async function scanDelete(
  redis: Redis,
  pattern: string,
): Promise<number> {
  const stream = redis.scanStream({ match: pattern, count: SCAN_BATCH });
  let deleted = 0;

  for await (const batch of stream as AsyncIterable<string[]>) {
    if (batch.length === 0) continue;

    // O SCAN pode devolver a mesma chave mais de uma vez (garantia do Redis é de
    // "pelo menos uma vez"), então o contador vem do retorno do UNLINK.
    const pipeline = redis.pipeline();
    for (const key of batch) pipeline.unlink(key);
    const results = await pipeline.exec();

    for (const [error, removed] of results ?? []) {
      if (!error && typeof removed === 'number') deleted += removed;
    }
  }

  return deleted;
}
