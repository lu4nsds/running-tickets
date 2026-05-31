import { request } from '@playwright/test';
import { writeFileSync, mkdirSync } from 'node:fs';
import { dirname } from 'node:path';
import { ACTIVE_EVENT_CACHE_PATH } from './fixtures/api.js';

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

        const eventsRes = await ctx.get('/api/events?per_page=20');
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

        let activeEvent = null;
        for (const ev of events) {
            const detailRes = await ctx.get(`/api/events/${ev.slug}`);
            if (!detailRes.ok()) continue;
            const detail = (await detailRes.json())?.data ?? {};

            const ticketTypes = (detail.ticket_types ?? []).filter(
                (t) => !t.is_sold_out && (t.available == null || t.available > 0)
            );
            const categories = detail.categories ?? [];
            if (ticketTypes.length === 0 || categories.length === 0) continue;

            activeEvent = {
                slug: ev.slug,
                id: detail.id ?? ev.id,
                title: detail.title ?? ev.title,
                ticketType: ticketTypes[0],
                category: categories[0],
            };
            break;
        }

        if (!activeEvent) {
            throw new Error(
                'Nenhum evento ativo com ticket type disponível encontrado. ' +
                'Rode "php artisan db:seed" na API.'
            );
        }

        mkdirSync(dirname(ACTIVE_EVENT_CACHE_PATH), { recursive: true });
        writeFileSync(ACTIVE_EVENT_CACHE_PATH, JSON.stringify(activeEvent));

        // eslint-disable-next-line no-console
        console.log(
            `[e2e] stack OK — evento ativo "${activeEvent.slug}" cacheado.`
        );
    } finally {
        await ctx.dispose();
    }
}
