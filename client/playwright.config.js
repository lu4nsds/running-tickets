import { defineConfig, devices } from '@playwright/test';

const CLIENT_URL = process.env.E2E_BASE_URL ?? 'http://localhost:5173';
const API_URL = process.env.E2E_API_URL ?? 'http://localhost';

export default defineConfig({
    testDir: './e2e',
    fullyParallel: false,
    workers: 1,
    forbidOnly: !!process.env.CI,
    // Sandbox do Mercado Pago retorna "internal_error" 500 com frequência
    // alta (~30-50%) mesmo para cartão APRO. Bumpamos retries para 2 (3
    // tentativas no total) — falhas reais do app ainda quebram.
    retries: 2,
    reporter: process.env.CI ? [['list'], ['html', { open: 'never' }]] : 'list',
    globalSetup: './e2e/global-setup.js',
    timeout: 90_000,
    expect: { timeout: 15_000 },
    use: {
        baseURL: CLIENT_URL,
        extraHTTPHeaders: { Accept: 'application/json' },
        trace: 'on-first-retry',
        video: 'retain-on-failure',
        screenshot: 'only-on-failure',
        actionTimeout: 15_000,
        navigationTimeout: 30_000,
    },
    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
    ],
    metadata: { apiUrl: API_URL },
});
