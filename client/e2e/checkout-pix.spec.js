import { test, expect } from '@playwright/test';
import { findActiveEvent } from './fixtures/api.js';
import { registerViaApi, loginViaToken } from './helpers/auth.js';
import {
    selectFirstAvailableTicket,
    fillFirstParticipant,
    proceedToPayment,
} from './helpers/checkout.js';
import {
    ensureBuyerInfo,
    selectPaymentTab,
    generatePix,
} from './helpers/mp.js';

/**
 * PIX: validamos até a geração do QR e o estado "Aguardando confirmação".
 * A confirmação efetiva depende de webhook externo do MP — fora do
 * escopo do E2E local.
 */
test.describe('Checkout autenticado — PIX', () => {
    test('gera QR PIX e mostra estado de aguardando confirmação', async ({
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

        await selectPaymentTab(page, 'pix');
        await ensureBuyerInfo(page);
        await generatePix(page);

        await expect(
            page.getByText(/Aguardando confirmação do PIX/i)
        ).toBeVisible();
    });
});
