import { expect } from '@playwright/test';

/**
 * Cartão de teste APRO (aprovação imediata) — Mercado Pago Brasil.
 * https://www.mercadopago.com.br/developers/pt/docs/your-integrations/test/cards
 */
export const APRO_CARD = {
    number: '5031 4332 1540 6351',
    holder: 'APRO',
    expiry: '11/30',
    cvv: '123',
    cpf: '123.456.789-09',
};

/**
 * PaymentView usa inputs nativos (não o widget Bricks com iframes) e tokeniza
 * via SDK só no submit — por isso podemos preencher diretamente.
 *
 * Para usuário autenticado os campos já vêm pré-preenchidos. Só sobrescrevemos
 * CPF (precisa ser único por execução) e mantemos o resto.
 */
export async function ensureBuyerInfo(page, { cpf } = {}) {
    // Default ao CPF do cartão APRO — o Mercado Pago sandbox aprova esse
    // pagador como pessoa válida. CPFs aleatórios podem causar rejeição
    // mesmo com checksum correto.
    const cpfValue = cpf ?? APRO_CARD.cpf;
    const cpfField = page.getByPlaceholder('000.000.000-00').first();
    await cpfField.fill(cpfValue);
    return { cpf: cpfValue };
}

export async function selectPaymentTab(page, tab) {
    const map = {
        credit: 'Cartão de Crédito',
        debit: 'Cartão de Débito',
        pix: 'PIX',
    };
    await page.getByRole('button', { name: map[tab], exact: true }).click();
}

export async function fillCreditCard(page, card = APRO_CARD) {
    await page.getByPlaceholder('0000 0000 0000 0000').fill(card.number);
    await page.getByPlaceholder('COMO APARECE NO CARTÃO').fill(card.holder);
    await page.getByPlaceholder('MM/AA').fill(card.expiry);
    await page.getByPlaceholder('000', { exact: true }).fill(card.cvv);

    // Aguarda o select de parcelas habilitar (sinal de que o BIN foi
    // reconhecido pelo SDK do Mercado Pago).
    await expect(
        page.locator('select').filter({ hasText: /parcela/i })
    ).toBeEnabled({ timeout: 15_000 });
}

export async function submitPayment(page) {
    await page
        .getByRole('button', { name: 'FINALIZAR PAGAMENTO', exact: true })
        .click();
}

export async function generatePix(page) {
    await page
        .getByRole('button', { name: 'GERAR QR CODE PIX', exact: true })
        .click();
    await expect(
        page.getByText(/Aguardando confirmação do PIX/i)
    ).toBeVisible({ timeout: 30_000 });
}

/**
 * APRO costuma aprovar em ~5-15s. Damos 90s de folga porque o
 * PaymentSuccessView faz polling de /orders/{ref}/status.
 */
export async function waitForPaymentSuccess(page) {
    await page.waitForURL(/\/pagamento\/sucesso/, { timeout: 90_000 });
    await expect(
        page.getByRole('heading', { name: /PAGAMENTO APROVADO/i })
    ).toBeVisible();
}
