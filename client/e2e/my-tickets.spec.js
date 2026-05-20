import { test, expect } from '@playwright/test';
import { registerViaApi, loginViaToken, loginViaUI } from './helpers/auth.js';

test.describe('Meus ingressos', () => {
    test('usuário novo acessa a página (estado vazio aceitável)', async ({
        page,
    }) => {
        const { token, profile } = await registerViaApi();
        await loginViaToken(page, { token, profile });

        await page.goto('/meus-ingressos');
        await expect(page).toHaveURL(/\/meus-ingressos/);
        // Sem assert sobre conteúdo — só que a SPA respondeu sem 404/redirect.
        await expect(page.locator('main, body').first()).toBeVisible();
    });

    test('usuário seed (joão) consegue acessar /meus-ingressos', async ({
        page,
    }) => {
        await loginViaUI(page, 'joao.org@teste.com', 'senha123');
        await page.goto('/meus-ingressos');
        await expect(page).toHaveURL(/\/meus-ingressos/);
    });
});
