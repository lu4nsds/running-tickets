import { request } from '@playwright/test';
import { readFileSync, existsSync } from 'node:fs';
import { resolve } from 'node:path';

const API_URL = process.env.E2E_API_URL ?? 'http://localhost';

export const ACTIVE_EVENT_CACHE_PATH = resolve(
    process.cwd(),
    'test-results/.active-event.json'
);

export async function withApi(fn) {
    const ctx = await request.newContext({ baseURL: API_URL });
    try {
        return await fn(ctx);
    } finally {
        await ctx.dispose();
    }
}

/**
 * Resolve em runtime um evento ativo (com ticket type disponível e
 * categoria) usando GET /api/events — não acopla testes a slugs fixos
 * que o EventSeeder gera com sufixos aleatórios.
 *
 * O global-setup já resolve e cacheia em ACTIVE_EVENT_CACHE_PATH; aqui
 * só lemos do cache. Fallback HTTP existe para o caso de o cache não
 * estar presente (ex.: rodando um spec via UI mode sem global-setup).
 */
export async function findActiveEvent() {
    if (existsSync(ACTIVE_EVENT_CACHE_PATH)) {
        try {
            return JSON.parse(readFileSync(ACTIVE_EVENT_CACHE_PATH, 'utf-8'));
        } catch {
            // cache corrompido — cai no fallback HTTP
        }
    }

    return withApi(async (ctx) => {
        const res = await ctx.get('/api/events?per_page=20');
        if (!res.ok()) {
            throw new Error(`GET /api/events falhou: ${res.status()}`);
        }
        const body = await res.json();
        const events = body?.data ?? [];

        for (const ev of events) {
            const detailRes = await ctx.get(`/api/events/${ev.slug}`);
            if (!detailRes.ok()) continue;
            const detail = (await detailRes.json())?.data ?? {};

            const ticketTypes = (detail.ticket_types ?? []).filter(
                (t) => !t.is_sold_out && (t.available == null || t.available > 0)
            );
            const categories = detail.categories ?? [];
            if (ticketTypes.length === 0 || categories.length === 0) continue;

            return {
                slug: ev.slug,
                id: detail.id ?? ev.id,
                title: detail.title ?? ev.title,
                ticketType: ticketTypes[0],
                category: categories[0],
            };
        }

        throw new Error(
            'Nenhum evento ativo com ticket type disponível encontrado. ' +
            'Rode "php artisan db:seed" na API.'
        );
    });
}
