// Baileys v7 é publicado como ESM-only (`"type": "module"`), mas este gateway é
// compilado como CommonJS. Um `import`/`require` estático do pacote quebraria em
// runtime, então carregamos o módulo via `import()` dinâmico.
//
// O truque do `Function` é intencional: com `module: "commonjs"` o tsc reescreve
// qualquer `import()` literal como `require()`, o que falharia ao carregar um
// pacote ESM-only. Construir o `import()` dentro de um `Function` esconde-o do
// compilador, preservando o import dinâmico nativo do Node no código emitido.
// eslint-disable-next-line @typescript-eslint/no-implied-eval -- proposital: esconde o import() do downleveling do tsc (ver acima).
const dynamicImport = new Function('m', 'return import(m)') as (
  m: string,
) => Promise<typeof import('baileys')>;

let cached: Promise<typeof import('baileys')> | null = null;

// Carrega (uma única vez) o pacote `baileys`. É a costura única por onde todo o
// código que precisa de *valores* do baileys passa — e o ponto de mock nos testes.
export function importBaileys(): Promise<typeof import('baileys')> {
  return (cached ??= dynamicImport('baileys'));
}
