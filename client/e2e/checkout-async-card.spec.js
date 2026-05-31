import { test, expect } from '@playwright/test';
import { findActiveEvent } from './fixtures/api.js';
import { registerViaApi, loginViaToken } from './helpers/auth.js';
import {
    selectFirstAvailableTicket,
    fillFirstParticipant,
    proceedToPayment,
} from './helpers/checkout.js';
import {
    APRO_CARD,
    OTHE_CARD,
    ensureBuyerInfo,
    selectPaymentTab,
    fillCreditCard,
    submitPayment,
    waitForPaymentProcessing,
    waitForOrderStatus,
    referenceFromUrl,
} from './helpers/mp.js';

test.describe('Pagamento async com cartão — fluxo de falha + retomada', () => {
    test('cartão recusado vira FAILED e usuário retoma via Meus Ingressos', async ({
        page,
    }) => {
        const { token, profile } = await registerViaApi();
        await loginViaToken(page, { token, profile });

        const ev = await findActiveEvent();
        await page.goto(`/eventos/${ev.slug}`);
        await selectFirstAvailableTicket(page);
        await page.waitForURL(/\/checkout$/, { timeout: 15_000 });
        await fillFirstParticipant(page);
        await proceedToPayment(page);

        const reference = referenceFromUrl(page);

        // 1ª tentativa: cartão OTHE → backend dispara job → status FAILED
        await selectPaymentTab(page, 'credit');
        await ensureBuyerInfo(page);
        await fillCreditCard(page, OTHE_CARD);
        await submitPayment(page);
        await waitForPaymentProcessing(page);

        await waitForOrderStatus(page, reference, 'failed');

        // Vai em Meus Ingressos e encontra o botão de repagamento
        await page.goto('/meus-ingressos');
        const efetuarBtn = page.getByRole('link', {
            name: /Efetuar Pagamento/i,
        });
        await expect(efetuarBtn).toBeVisible({ timeout: 15_000 });
        await efetuarBtn.click();

        // De volta à tela de pagamento — mesmo Order
        await page.waitForURL(
            new RegExp(`/pagamento/${reference}(?:[/?]|$)`),
            { timeout: 15_000 }
        );

        // 2ª tentativa: cartão APRO
        await selectPaymentTab(page, 'credit');
        await ensureBuyerInfo(page);
        await fillCreditCard(page, APRO_CARD);
        await submitPayment(page);
        await waitForPaymentProcessing(page);

        await waitForOrderStatus(page, reference, 'paid');
    });
});
