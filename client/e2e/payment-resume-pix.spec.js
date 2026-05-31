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
    referenceFromUrl,
} from './helpers/mp.js';

test.describe('Retomada do PIX após sair da tela', () => {
    test('usuário fecha a aba do QR e retoma via Meus Ingressos com o mesmo código', async ({
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

        await selectPaymentTab(page, 'pix');
        await ensureBuyerInfo(page);
        await generatePix(page);

        const pixCodeLocator = page.locator('p, span').filter({
            hasText: /^0002\d/,
        }).first();
        const originalQrCode = (await pixCodeLocator.textContent()).trim();
        expect(originalQrCode.length).toBeGreaterThan(20);

        // Usuário "fecha a aba" — sai pra outra rota
        await page.goto('/');
        await page.goto('/meus-ingressos');

        const efetuarBtn = page.getByRole('link', {
            name: /Efetuar Pagamento/i,
        });
        await expect(efetuarBtn).toBeVisible({ timeout: 15_000 });
        await efetuarBtn.click();

        await page.waitForURL(
            new RegExp(`/pagamento/${reference}(?:[/?]|$)`),
            { timeout: 15_000 }
        );

        // QR Code é o mesmo — backend reaproveitou o payment_response_body.
        const resumedQrCode = (await pixCodeLocator.textContent()).trim();
        expect(resumedQrCode).toBe(originalQrCode);
    });
});
