# Vue 3 + Vite

This template should help get you started developing with Vue 3 in Vite. The template uses Vue 3 `<script setup>` SFCs, check out the [script setup docs](https://v3.vuejs.org/api/sfc-script-setup.html#sfc-script-setup) to learn more.

Learn more about IDE Support for Vue in the [Vue Docs Scaling up Guide](https://vuejs.org/guide/scaling-up/tooling.html#ide-support).

## Testes E2E (Playwright)

Os testes ficam em [e2e/](e2e/) e batem na **stack real** (Docker Compose). Eles **nunca rodam `migrate:fresh`** — usam o que estiver no banco.

### Pré-requisitos

1. Stack subida: `docker compose up -d` (na raiz do repo).
2. Pelo menos 1 evento ativo no banco. Se a base estiver vazia: `docker compose exec api php artisan db:seed`.
3. Credenciais **sandbox** do Mercado Pago em `api/.env` (`MERCADOPAGO_ACCESS_TOKEN`) e em `client/.env` (`VITE_MERCADOPAGO_PUBLIC_KEY`).
4. Instalar browsers do Playwright (1ª vez): `npm run test:e2e:install`.

### Comandos

```bash
npm run test:e2e         # roda toda a suíte (headless)
npm run test:e2e:ui      # roda no UI mode (debug visual)
```

Variáveis opcionais: `E2E_BASE_URL` (default `http://localhost:5173`), `E2E_API_URL` (default `http://localhost`).

### Cobertura

- `auth.spec.js` — cadastro, login, login inválido, logout, esqueci a senha.
- `events-browse.spec.js` — home, listagem, detalhe do evento.
- `checkout-guest-card.spec.js` — guest se cadastra no meio do fluxo e paga com cartão APRO.
- `checkout-auth-card.spec.js` — usuário autenticado paga com APRO e vê o pedido em `/meus-ingressos`.
- `checkout-pix.spec.js` — geração de QR PIX (não confirma pagamento — depende de webhook externo do MP).
- `my-tickets.spec.js` — acesso autenticado à área de ingressos.
- `route-guards.spec.js` — proteção de rotas + redirect pós-login.

Cartão de teste APRO (centralizado em `e2e/helpers/mp.js`): `5031 4332 1540 6351`, CVV `123`, 11/30, CPF `123.456.789-09`, titular `APRO`.

### Flakiness do sandbox Mercado Pago

O ambiente de teste do MP devolve `internal_error 500` esporadicamente (até ~30-50% das vezes em períodos ruins) — tanto para cartão (`circuit breaker open` no Bricks) quanto para PIX (`http is unavailable for request create_ti`). Por isso `playwright.config.js` usa `retries: 2`. Se a suíte vermelhar nos testes de pagamento, **rode novamente** — não é regressão do app, é instabilidade do MP. Confirme nos logs com `docker compose logs api | grep "Erro.*Mercado Pago"`.
