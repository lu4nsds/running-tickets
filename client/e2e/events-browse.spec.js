import { test, expect } from '@playwright/test';
import { findActiveEvent } from './fixtures/api.js';

test.describe('Navegação de eventos', () => {
    test('home renderiza próximos eventos', async ({ page }) => {
        await page.goto('/');
        await expect(
            page.getByRole('heading', { name: /Próximos Eventos/i })
        ).toBeVisible({ timeout: 15_000 });
        // pelo menos 1 botão "Ver Detalhes" presente
        await expect(
            page.getByRole('button', { name: 'Ver Detalhes' }).first()
        ).toBeVisible();
    });

    test('/eventos lista pelo menos um evento', async ({ page }) => {
        await page.goto('/eventos');
        await expect(
            page.getByRole('heading', { name: 'Todos os Eventos' })
        ).toBeVisible({ timeout: 15_000 });
        const cards = page.getByRole('button', { name: 'Ver Detalhes' });
        await expect(cards.first()).toBeVisible();
        expect(await cards.count()).toBeGreaterThan(0);
    });

    test('clicar em "Ver Detalhes" abre página de detalhe com CTA', async ({ page }) => {
        await page.goto('/eventos');
        await page.getByRole('button', { name: 'Ver Detalhes' }).first().click();
        await page.waitForURL(/\/eventos\/[^/]+$/, { timeout: 15_000 });

        // Status "ATIVO" e h3 com nome do ticket type
        await expect(page.getByText('ATIVO').first()).toBeVisible();
        await expect(page.getByRole('heading', { level: 3 }).first()).toBeVisible();
    });

    test('detalhe via slug resolvido pela API', async ({ page }) => {
        const ev = await findActiveEvent();
        await page.goto(`/eventos/${ev.slug}`);
        await expect(
            page.getByRole('heading', { level: 1 })
        ).toBeVisible({ timeout: 15_000 });
        await expect(page.getByText('Inscrições')).toBeVisible();
    });
});
