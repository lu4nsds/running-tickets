import { test, expect } from '@playwright/test';
import { findActiveEvent } from './fixtures/api.js';
import { registerViaApi, loginViaToken, generateCPF } from './helpers/auth.js';
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
    waitForPaymentSuccess,
} from './helpers/mp.js';

test.describe('Checkout autenticado — cartão APRO', () => {
    test('fluxo completo: seleção → checkout → pagamento aprovado', async ({
        page,
    }) => {
        const { token, profile } = await registerViaApi();
        await loginViaToken(page, { token, profile });

        const ev = await findActiveEvent();
        await page.goto(`/eventos/${ev.slug}`);

        await selectFirstAvailableTicket(page);

        // autenticado → pula /checkout/identificacao
        await page.waitForURL(/\/checkout$/, { timeout: 15_000 });

        await fillFirstParticipant(page);
        await proceedToPayment(page);

        // /pagamento → confirma pedido criado
        await expect(
            page.getByText(/Pedido:\s*ORD-/i)
        ).toBeVisible({ timeout: 15_000 });

        await selectPaymentTab(page, 'credit');
        await ensureBuyerInfo(page);
        await fillCreditCard(page, APRO_CARD);
        await submitPayment(page);
        await waitForPaymentSuccess(page);

        // Link para "Ver Meus Ingressos" deve aparecer
        await expect(
            page.getByRole('link', { name: /Ver Meus Ingressos/i })
        ).toBeVisible();
    });
});
