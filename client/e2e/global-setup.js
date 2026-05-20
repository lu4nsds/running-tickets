import { request } from '@playwright/test';

const API_URL = process.env.E2E_API_URL ?? 'http://localhost';

export default async function globalSetup() {
    const ctx = await request.newContext({ baseURL: API_URL });

    try {
        const health = await ctx.get('/api/health').catch(() => null);
        if (!health || !health.ok()) {
            throw new Error(
                `API não respondeu em ${API_URL}/api/health. ` +
                `Suba a stack com "docker compose up -d" antes de rodar os testes.`
            );
        }

        const eventsRes = await ctx.get('/api/events?per_page=1');
        if (!eventsRes.ok()) {
            throw new Error(
                `Falha ao consultar /api/events (status ${eventsRes.status()}).`
            );
        }
        const body = await eventsRes.json();
        const events = body?.data ?? [];
        if (events.length === 0) {
            throw new Error(
                'Nenhum evento ativo encontrado no banco. ' +
                'Rode "docker compose exec api php artisan db:seed" (SEM migrate:fresh) ' +
                'para popular dados de demonstração.'
            );
        }

        // eslint-disable-next-line no-console
        console.log(`[e2e] stack OK — ${events.length}+ evento(s) ativo(s) disponíveis.`);
    } finally {
        await ctx.dispose();
    }
}
