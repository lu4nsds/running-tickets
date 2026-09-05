export interface ScanCall {
  match: string;
  count: number;
}

interface FakePipeline {
  unlink: jest.Mock<FakePipeline, [string]>;
  exec: jest.Mock<Promise<[null, number][]>, []>;
}

// Parte de SCAN/UNLINK compartilhada pelos FakeRedis dos specs. Devolve uma chave
// sintética por padrão, o bastante para exercitar o caminho de limpeza sem
// reimplementar um Redis inteiro, e registra o que foi varrido/removido para as
// asserções.
export function fakeScanSupport(): {
  scans: ScanCall[];
  unlinked: string[];
  scanStream: jest.Mock;
  pipeline: jest.Mock;
} {
  const scans: ScanCall[] = [];
  const unlinked: string[] = [];

  // Async iterable puro em vez de um `Readable`: um stream de verdade avança por
  // process.nextTick/setImmediate, que os fake timers do Jest interceptam, e a
  // limpeza travaria nos specs que os usam. O caminho com stream real está
  // coberto em `redis-scan.util.spec.ts`.
  const scanStream = jest.fn((options: ScanCall) => {
    scans.push(options);
    const batches = [[options.match.replace('*', 'k')]];
    return {
      // eslint-disable-next-line @typescript-eslint/require-await
      [Symbol.asyncIterator]: async function* () {
        yield* batches;
      },
    };
  });

  const pipeline = jest.fn((): FakePipeline => {
    const queued: string[] = [];
    const chain: FakePipeline = {
      unlink: jest.fn((key: string) => {
        queued.push(key);
        unlinked.push(key);
        return chain;
      }),
      exec: jest.fn(() =>
        Promise.resolve(queued.map(() => [null, 1] as [null, number])),
      ),
    };
    return chain;
  });

  return { scans, unlinked, scanStream, pipeline };
}
