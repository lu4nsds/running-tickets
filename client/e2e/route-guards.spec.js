import { test, expect } from '@playwright/test';
import { registerViaApi, loginViaToken } from './helpers/auth.js';

test.describe('Route guards', () => {
    test('rota protegida sem token redireciona para /entrar com ?redirect', async ({
        page,
    }) => {
        await page.goto('/meus-ingressos');
        await page.waitForURL(/\/entrar/, { timeout: 10_000 });
        expect(page.url()).toMatch(/redirect=.*meus-ingressos/);
    });

    test('após login, query "redirect" leva o usuário de volta', async ({ page }) => {
        const { user } = await registerViaApi();

        await page.goto('/meus-ingressos');
        await page.waitForURL(/\/entrar/, { timeout: 10_000 });

        await page.getByRole('textbox', { name: 'E-mail' }).fill(user.email);
        await page.getByRole('textbox', { name: 'Senha' }).fill(user.password);
        await page.getByRole('button', { name: 'Entrar', exact: true }).click();

        await page.waitForURL(/\/meus-ingressos/, { timeout: 15_000 });
    });

    test('/checkout sem dados de checkout reroteia para fora', async ({ page }) => {
        const { token, profile } = await registerViaApi();
        await loginViaToken(page, { token, profile });

        // limpa qualquer checkoutData persistido
        await page.evaluate(() => sessionStorage.clear());

        await page.goto('/checkout');
        // Aceita home (/) ou /eventos como destino — o router/onMounted
        // do CheckoutIdentificacaoView faz replace para home quando não há
        // checkoutData.
        await page.waitForURL(
            (url) => /^\/(eventos)?\/?$/.test(url.pathname),
            { timeout: 15_000 }
        );
    });
});
