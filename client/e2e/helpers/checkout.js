import { expect } from '@playwright/test';
import { generateCPF } from './auth.js';

/**
 * Em EventDetailsView, incrementa qty do 1º ticket type. Os botões +/- não
 * têm aria-label/text — usamos o input readonly que fica entre eles para
 * achar o botão "+" como sibling-imediato.
 */
export async function selectFirstAvailableTicket(page) {
    // Espera os ticket types carregarem
    await expect(page.getByRole('heading', { level: 3 }).first()).toBeVisible({
        timeout: 20_000,
    });

    // O input readonly com value="0" tem o botão "+" como próximo elemento.
    // Após o click o value vira "1", então este seletor casa só uma vez.
    const plusBtn = page
        .locator('input[readonly][type="text"] + button')
        .first();
    await plusBtn.click();

    // CTA "Informar participantes" só aparece depois de qty >= 1
    await page
        .getByRole('button', { name: /Informar participantes/i })
        .click();
}

/**
 * Preenche o 1º participante em CheckoutView. Para usuário autenticado,
 * nome/email já vêm pré-preenchidos — sobrescrevemos só se data.name/email
 * for passado, ou usamos o valor existente.
 */
export async function fillFirstParticipant(page, data = {}) {
    const name = data.name ?? `Participante E2E ${Date.now()}`;
    const email = data.email ?? `e2e-part-${Date.now()}@runningtickets-e2e.com`;
    const cpf = data.cpf ?? generateCPF();
    const phone = data.phone ?? '(11) 99999-9999';
    const birthdate = data.birthdate ?? '1990-05-15';

    // Categoria — 1ª opção real (pula placeholder com value="")
    const categorySelect = page.locator('select').first();
    await categorySelect.waitFor({ state: 'visible' });
    const firstValue = await categorySelect
        .locator('option:not([value=""])')
        .first()
        .getAttribute('value');
    if (firstValue) await categorySelect.selectOption(firstValue);

    // Nome/email podem vir pré-preenchidos (usuário autenticado) —
    // sobrescrevemos sempre para garantir estado conhecido.
    await page
        .getByRole('textbox', { name: 'João Silva Santos' })
        .first()
        .fill(name);

    await page
        .getByRole('textbox', { name: 'joao@exemplo.com' })
        .first()
        .fill(email);

    await page
        .getByRole('textbox', { name: '(11) 99999-9999' })
        .first()
        .fill(phone);

    await page
        .getByRole('textbox', { name: '000.000.000-00' })
        .first()
        .fill(cpf);

    // Data de Nascimento: <input type="date"> — fill com YYYY-MM-DD
    await page.locator('input[type="date"]').first().fill(birthdate);

    return { name, email, cpf, phone, birthdate };
}

export async function proceedToPayment(page) {
    const btn = page.getByRole('button', {
        name: /Prosseguir para o Pagamento/i,
    });
    await expect(btn).toBeEnabled({ timeout: 10_000 });
    await btn.click();
    await page.waitForURL(/\/pagamento(\/|\?|$)/, { timeout: 30_000 });
}
