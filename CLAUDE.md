# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Read this first

The authoritative project manifesto lives at [.claude/CLAUDE.md](.claude/CLAUDE.md). It indexes the rule files ([.claude/rules/](.claude/rules/)), workflows ([.claude/workflows/](.claude/workflows/)), and decision/tech-debt memory ([.claude/memory/](.claude/memory/)). Read it before doing anything non-trivial — do not infer standards from existing code, which has known debt.

This root file only covers what the manifesto does not: the boot/test/deploy commands and a one-screen orientation.

## Repo shape

Four apps + supporting services, all wired together by [docker-compose.yml](docker-compose.yml):

- [api/](api/) — Laravel 12 / PHP 8.2 backend, served by Nginx on `:80`
- [client/](client/) — Vue 3 + Vite public ticket shop, dev server on `:5173`
- [admin/](admin/) — Vue 3 + Vite organizer/super-admin backoffice, dev server on `:5174`
- [whatsapp-gateway/](whatsapp-gateway/) — NestJS + Baileys WhatsApp service on `:3000`
- Services: MySQL 8.4 (`:3306`), Redis (`:6379`), Mailpit UI (`:8025`)
- Background containers: `queue-worker` (`php artisan queue:work`) and `scheduler` (`php artisan schedule:work`)

## Common commands

### Boot the stack

```bash
docker compose up -d              # all services
docker compose logs -f api        # tail one service
docker compose exec api bash      # shell into the API container
```

The `client`, `admin`, and `whatsapp-gateway` containers each run `npm i && npm run dev` (or `start:dev`) on boot — no manual install needed for the first run.

### API ([api/](api/)) — run inside the `api` container or on host

```bash
composer setup                    # first-time install + .env + key + migrate + npm build
composer dev                      # concurrent: artisan serve + queue:listen + pail + vite
composer test                     # config:clear + phpunit
php artisan test                  # run tests directly
php artisan test --filter=Name    # single test / pattern
./vendor/bin/pint                 # lint (Laravel Pint)
php artisan migrate               # apply migrations
php artisan migrate:fresh --seed  # wipe + reseed
php artisan queue:work            # worker (already running as `queue-worker` container)
php artisan pail                  # tail application logs
```

### Frontends ([client/](client/), [admin/](admin/))

Identical scripts in both:

```bash
npm install
npm run dev        # Vite dev server
npm run build
npm run preview
```

No lint or test scripts are defined for the Vue apps.

### WhatsApp Gateway ([whatsapp-gateway/](whatsapp-gateway/))

```bash
npm run start:dev          # nest start --watch
npm run build              # nest build
npm test                   # Jest
npm test -- <pattern>      # single test
npm run test:e2e
npm run lint               # eslint --fix
```

### Build / deploy helpers ([scripts/](scripts/))

- `build-and-test-api.sh`, `build-and-test-whatsapp-gateway.sh` — CI builds
- `build-and-push-api.sh`, `build-and-push-whatsapp-gateway.sh` — image push
- `deploy-client.sh`, `deploy-admin.sh` — frontend deploys

## Architecture orientation

Multi-tenant B2B2C ticketing for Brazilian sports events. **Organizer is the tenant unit**; isolation is enforced at the application layer (middleware + scoped queries), not the DB.

Two Vue SPAs (public `client/`, backoffice `admin/`) consume one Laravel API behind Nginx, authenticated with Sanctum bearer tokens stored in `localStorage`. Mercado Pago handles payments; S3 stores event banners served via an API proxy route.

Ticket generation is asynchronous: a paid `Order` triggers `OrderObserver` → `GenerateOrderTicketsJob` on the Redis queue (the Mercado Pago webhook must respond in under 5s — see AD-008 in [.claude/memory/known-decisions.md](.claude/memory/known-decisions.md)).

The WhatsApp Gateway is an independent NestJS service using Baileys for WhatsApp Web protocol, with Redis-backed auth/message state — the Laravel API talks to it over HTTP on `:3000`.

For request lifecycle, auth flows, layer responsibilities, domain rules, and the tech-debt register, follow the index in [.claude/CLAUDE.md](.claude/CLAUDE.md).

## Non-negotiable rules

The project has 11 non-negotiable rules (authorization via Policies, money as integer centavos, ticket codes as UUIDs, business logic in Services not controllers, etc.). They are listed in [.claude/CLAUDE.md § Non-Negotiable Rules](.claude/CLAUDE.md) — always check before adding code.
