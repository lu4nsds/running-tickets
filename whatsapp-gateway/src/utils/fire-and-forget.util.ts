import type { Logger } from '@nestjs/common';

// Node encerra o processo em unhandled rejection (padrão desde a v15), então uma
// promise disparada e esquecida com `void` dentro de um event handler do Baileys
// derruba o gateway inteiro — todos os tenants — a cada soluço do Redis.
//
// `fireAndForget` é a única forma permitida de não esperar por uma promise: anexa
// um catch que loga e engole o erro, mantendo o processo vivo.
export function fireAndForget(
  promise: Promise<unknown>,
  logger: Logger,
  context: string,
): void {
  // `Promise.resolve` devolve a própria promise quando já é nativa, então isto não
  // muda o comportamento — só impede que um chamador que devolveu `undefined`
  // (ou lançou de forma síncrona) estoure dentro do próprio guarda.
  Promise.resolve(promise).catch((error: unknown) => {
    const message = error instanceof Error ? error.message : String(error);
    logger.error(`${context}: ${message}`);
  });
}
