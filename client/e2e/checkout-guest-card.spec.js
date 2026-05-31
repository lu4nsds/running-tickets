import { test, expect } from '@playwright/test';
import { findActiveEvent } from './fixtures/api.js';
import { makeTestUser } from './helpers/auth.js';
import {
    selectFirstAvailableTicket,
    fillFirstParticipant,
    proceedToPayment,
} from './helpers/checkout.js';
import {
    APRO_CARD,
    ensureBuyerInfo,
    selectPaymentTab,
    fillCreditCard,
    submitPayment,
    waitForPaymentProcessing,
    waitForOrderStatus,
    referenceFromUrl,
} from './helpers/mp.js';

/**
 * CheckoutIdentificacaoView é uma tela-gate: pede login/cadastro.
 * Aqui o guest escolhe "Cadastre-se" e completa o fluxo.
 */
test.describe('Checkout guest → cadastro → cartão APRO', () => {
    test('guest se cadastra no meio do fluxo e finaliza pagamento', async ({
        page,
        context,
    }) => {
        // Garante estado guest
        await context.clearCookies();
        await page.goto('/');
        await page.evaluate(() => {
            localStorage.clear();
            sessionStorage.clear();
        });

        const ev = await findActiveEvent();
        await page.goto(`/eventos/${ev.slug}`);
        await selectFirstAvailableTicket(page);

        // Gate de auth
        await page.waitForURL(/\/checkout\/identificacao/, { timeout: 15_000 });
        await page.getByRole('button', { name: /Cadastre-se/i }).click();
        await page.waitForURL(/\/cadastro/, { timeout: 10_000 });

        const user = makeTestUser();
        await page.getByRole('textbox', { name: 'Nome Completo' }).fill(user.name);
        await page.getByRole('textbox', { name: 'E-mail' }).fill(user.email);
        await page.getByRole('textbox', { name: /^Senha/ }).fill(user.password);
        await page
            .getByRole('textbox', { name: 'Confirmar Senha' })
            .fill(user.password_confirmation);
        const submit = page.getByRole('button', { name: 'Cadastrar', exact: true });
        await expect(submit).toBeEnabled({ timeout: 5_000 });
        await submit.click();

        // Após cadastro vai para /verificar-email. Para retomar o checkout
        // de forma limpa, voltamos ao evento e re-adicionamos ao carrinho —
        // dessa vez já autenticados, o gate de /checkout/identificacao é
        // pulado.
        await page.waitForURL(/\/verificar-email/, { timeout: 15_000 });
        await page.goto(`/eventos/${ev.slug}`);
        await selectFirstAvailableTicket(page);
        await page.waitForURL(/\/checkout$/, { timeout: 15_000 });

        await fillFirstParticipant(page, { name: user.name, email: user.email });
        await proceedToPayment(page);

        const reference = referenceFromUrl(page);

        await selectPaymentTab(page, 'credit');
        await ensureBuyerInfo(page);
        await fillCreditCard(page, APRO_CARD);
        await submitPayment(page);
        await waitForPaymentProcessing(page);

        await waitForOrderStatus(page, reference, 'paid');
    });
});
