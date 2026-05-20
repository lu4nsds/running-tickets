import { request, expect } from '@playwright/test';

const API_URL = process.env.E2E_API_URL ?? 'http://localhost';

/**
 * Gera credenciais únicas por chamada — reruns sem reset de banco não colidem.
 */
export function makeTestUser(overrides = {}) {
    const stamp = `${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
    return {
        name: `E2E User ${stamp}`,
        email: `e2e-${stamp}@runningtickets-e2e.com`,
        password: 'Senha123Teste',
        password_confirmation: 'Senha123Teste',
        ...overrides,
    };
}

/**
 * Gera um CPF válido aleatório. Importante: o backend valida unicidade do
 * CPF do participante, então cada teste de checkout precisa do seu próprio.
 */
export function generateCPF() {
    const rand = (n) => Math.floor(Math.random() * n);
    const n = Array.from({ length: 9 }, () => rand(10));

    const calc = (base) => {
        const sum = base.reduce(
            (s, d, i) => s + d * (base.length + 1 - i),
            0
        );
        const rest = (sum * 10) % 11;
        return rest === 10 ? 0 : rest;
    };

    const d1 = calc(n);
    const d2 = calc([...n, d1]);
    const digits = [...n, d1, d2].join('');
    return `${digits.slice(0, 3)}.${digits.slice(3, 6)}.${digits.slice(6, 9)}-${digits.slice(9)}`;
}

/**
 * Cria usuário via API (mais rápido e isolado do fluxo da UI).
 */
export async function registerViaApi(overrides = {}) {
    const user = makeTestUser(overrides);
    const ctx = await request.newContext({ baseURL: API_URL });
    try {
        const res = await ctx.post('/api/auth/register', {
            data: user,
            headers: { Accept: 'application/json' },
        });
        if (!res.ok()) {
            throw new Error(
                `register falhou (${res.status()}): ${await res.text()}`
            );
        }
        const body = await res.json();
        return { user, token: body.token, profile: body.user };
    } finally {
        await ctx.dispose();
    }
}

/**
 * Login via UI usando os campos com label "E-mail" e "Senha".
 */
export async function loginViaUI(page, email, password) {
    await page.goto('/entrar');
    await page.getByRole('textbox', { name: 'E-mail' }).fill(email);
    await page.getByRole('textbox', { name: 'Senha' }).fill(password);
    await page.getByRole('button', { name: 'Entrar', exact: true }).click();
    await page.waitForURL((url) => !url.pathname.startsWith('/entrar'), {
        timeout: 15_000,
    });
}

/**
 * Injeta token no localStorage. Útil quando o spec quer começar autenticado
 * sem testar o fluxo de login em si.
 */
export async function loginViaToken(page, { token, profile }) {
    await page.goto('/');
    await page.evaluate(
        ({ token, profile }) => {
            localStorage.setItem('auth_token', token);
            if (profile) localStorage.setItem('user', JSON.stringify(profile));
        },
        { token, profile }
    );
    await page.reload();
}

export async function expectAuthenticated(page) {
    const token = await page.evaluate(() => localStorage.getItem('auth_token'));
    expect(token, 'auth_token deve estar no localStorage').toBeTruthy();
}
