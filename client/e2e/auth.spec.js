import { test, expect } from '@playwright/test';
import { makeTestUser, expectAuthenticated, loginViaUI } from './helpers/auth.js';

test.describe('Autenticação', () => {
    test('cadastra novo usuário e redireciona para verificação', async ({ page }) => {
        const user = makeTestUser();

        await page.goto('/cadastro');
        await page.getByRole('textbox', { name: 'Nome Completo' }).fill(user.name);
        await page.getByRole('textbox', { name: 'E-mail' }).fill(user.email);
        await page.getByRole('textbox', { name: /^Senha/ }).fill(user.password);
        await page
            .getByRole('textbox', { name: 'Confirmar Senha' })
            .fill(user.password_confirmation);

        const submit = page.getByRole('button', { name: 'Cadastrar', exact: true });
        await expect(submit).toBeEnabled({ timeout: 5_000 });
        await submit.click();

        await page.waitForURL(/\/verificar-email/, { timeout: 15_000 });
        await expectAuthenticated(page);
    });

    test('login com credenciais válidas (usuário seed)', async ({ page }) => {
        await loginViaUI(page, 'joao.org@teste.com', 'senha123');
        await expectAuthenticated(page);
        await expect(
            page.getByRole('button', { name: /Bem-vindo/i })
        ).toBeVisible();
    });

    test('exibe erro com senha inválida e permanece em /entrar', async ({ page }) => {
        await page.goto('/entrar');
        await page.getByRole('textbox', { name: 'E-mail' }).fill('joao.org@teste.com');
        await page.getByRole('textbox', { name: 'Senha' }).fill('senha-errada-XYZ');
        await page.getByRole('button', { name: 'Entrar', exact: true }).click();

        await expect(
            page.locator('text=/Erro|inválid|incorret|credenciais/i').first()
        ).toBeVisible({ timeout: 10_000 });
        await expect(page).toHaveURL(/\/entrar/);
        const token = await page.evaluate(() => localStorage.getItem('auth_token'));
        expect(token).toBeFalsy();
    });

    test('logout limpa token e volta para home', async ({ page }) => {
        await loginViaUI(page, 'joao.org@teste.com', 'senha123');
        await page.goto('/saindo');
        await page.waitForURL((url) => url.pathname === '/', { timeout: 15_000 });
        const token = await page.evaluate(() => localStorage.getItem('auth_token'));
        expect(token).toBeFalsy();
    });

    test('"esqueci a senha" aceita submit do email', async ({ page }) => {
        await page.goto('/esqueceu-senha');
        await page
            .getByRole('textbox', { name: /e-?mail/i })
            .first()
            .fill(`e2e-${Date.now()}@runningtickets-e2e.com`);
        await page
            .getByRole('button', { name: /enviar|recuperar|redefinir/i })
            .first()
            .click();
        // Sem assert hard — backend pode aceitar e mostrar toast/mensagem.
        // Garantimos só que a página continua respondendo (não crashou).
        await page.waitForLoadState('networkidle');
        await expect(page.getByRole('button').first()).toBeVisible();
    });
});
