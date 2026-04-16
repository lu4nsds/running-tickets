# Running Tickets — Project Manifesto

> **Claude: read this file first.** It is the authoritative entry point for every session.
> Current code = source of **business truth**. `rules/` = source of **technical standards**.
> Never use existing code patterns as the style guide unless they match the standards below.

---

## What Is This Project?

**Running Tickets** is a multi-tenant B2B2C e-ticketing platform for sports events (marathons, road races, triathlons), operating in Brazil.

| Role | Can Do |
|------|--------|
| **Super Admin** | Manage all organizers, events, payouts, platform config |
| **Organizer Admin** | Manage own events, configure Mercado Pago, add staff |
| **Organizer Staff** | Validate tickets via QR scanner (read-only otherwise) |
| **End User** | Browse events, buy tickets, download QR codes |

**Tech Stack:**
| Layer | Tech |
|-------|------|
| Backend API | PHP 8.2 + Laravel 12 (`api/`) |
| Frontend — public | Vue 3 + Pinia + Tailwind 3.4 (`client/`) |
| Frontend — admin | Vue 3 + Pinia + Tailwind 3.4 + ApexCharts (`admin/`) |
| Database | MySQL 8.4 |
| Queue / Cache | Redis |
| Auth | Laravel Sanctum (bearer tokens) + Google OAuth (Socialite) |
| Payments | Mercado Pago SDK |
| Storage | AWS S3 (event banners, served via API proxy) |
| Email | Resend |

---

## Repository Layout

```
running-tickets/
├── api/                    # Laravel 12 backend
│   ├── app/
│   │   ├── Enums/          # PHP backed enums for all status fields
│   │   ├── Http/
│   │   │   ├── Controllers/Api/   # Thin controllers — no business logic
│   │   │   ├── Middleware/
│   │   │   ├── Requests/          # Form Requests (validation)
│   │   │   └── Resources/         # API Resources (response shaping)
│   │   ├── Jobs/           # Queued async work
│   │   ├── Mail/           # Mailable classes
│   │   ├── Models/         # Eloquent models
│   │   ├── Observers/      # Model lifecycle hooks
│   │   ├── Policies/       # Authorization (one per model — TARGET)
│   │   └── Services/       # Business logic (one class per concern — TARGET)
│   ├── database/migrations/
│   └── routes/api.php
├── client/                 # Vue 3 public app (ticket buyers)
│   └── src/
│       ├── api/            # Axios modules per domain
│       ├── composables/    # Reusable reactive logic
│       ├── router/
│       ├── stores/         # Pinia stores
│       └── views/
├── admin/                  # Vue 3 admin app
│   └── src/               # (same structure as client/)
├── .claude/               # ← YOU ARE HERE
│   ├── CLAUDE.md          # This file — project manifesto
│   ├── rules/             # Standards for every code layer
│   ├── workflows/         # Step-by-step task guides
│   └── memory/            # Decisions and tech debt register
└── docker-compose.yml
```

---

## Rules & Standards Index

| File | Use When |
|------|---------|
| [rules/architecture.md](rules/architecture.md) | Understanding system components, auth flows, request lifecycle, queue pipeline |
| [rules/backend-standards.md](rules/backend-standards.md) | Writing Laravel code (controllers, services, policies, jobs, models, tests) |
| [rules/frontend-standards.md](rules/frontend-standards.md) | Writing Vue 3 code (components, stores, composables, API modules, routing) |
| [rules/database-standards.md](rules/database-standards.md) | Writing migrations, designing schema, adding indexes |
| [rules/business-domains.md](rules/business-domains.md) | Understanding business rules, domain entities, workflows, glossary |
| [workflows/new-feature.md](workflows/new-feature.md) | Implementing any new feature end-to-end (full checklist) |
| [workflows/refactor-flow.md](workflows/refactor-flow.md) | Safely refactoring existing code (patterns + safety rules) |
| [memory/tech-debt.md](memory/tech-debt.md) | Checking known issues before touching existing code |
| [memory/known-decisions.md](memory/known-decisions.md) | Checking architectural decisions before proposing alternatives |

---

## Quick-Start by Task Type

### New Feature
1. [rules/business-domains.md](rules/business-domains.md) — identify the domain
2. [rules/architecture.md](rules/architecture.md) — trace where it fits
3. [workflows/new-feature.md](workflows/new-feature.md) — follow the checklist
4. Apply [rules/backend-standards.md](rules/backend-standards.md) + [rules/frontend-standards.md](rules/frontend-standards.md)

### Bug Fix
1. [rules/business-domains.md](rules/business-domains.md) — identify the domain
2. [memory/tech-debt.md](memory/tech-debt.md) — check if it's a known debt item
3. [rules/architecture.md](rules/architecture.md) — trace the request path

### Refactor
1. [workflows/refactor-flow.md](workflows/refactor-flow.md) — pick the right pattern
2. [memory/tech-debt.md](memory/tech-debt.md) — pick a registered item
3. Apply the relevant standard rule file

### Database Migration
1. [rules/database-standards.md](rules/database-standards.md) — naming, indexes, column types
2. [memory/known-decisions.md](memory/known-decisions.md) — check AD-001 (money in cents), AD-002 (UUID codes)

### Understanding a Business Rule
1. [rules/business-domains.md](rules/business-domains.md) — definitive domain reference
2. Then read the relevant model in `api/app/Models/`

---

## Non-Negotiable Rules

> These apply in every session. No exceptions.

1. **Never use existing code as a style guide.** The codebase has tech debt. Use `.claude/rules/` instead.
2. **Authorization → Policies.** Never write `if ($user->role === ...)` in a controller. Use `$this->authorize()` and `app/Policies/`.
3. **Business logic → Services.** Controllers receive, delegate, respond. Logic lives in `app/Services/`.
4. **API calls → `src/api/*.js` modules.** Vue components never import axios directly.
5. **Never use raw SQL.** Eloquent + query scopes only.
6. **Money is always integers (centavos).** `price_cents INTEGER`, never `DECIMAL`. See [AD-001](memory/known-decisions.md).
7. **Ticket codes are UUIDs.** Order references are `ORD-YYYY-RANDOM`. See [AD-002](memory/known-decisions.md), [AD-003](memory/known-decisions.md).
8. **Portuguese is allowed in UI strings and route paths.** Code, files, and DB columns must be English. (Exception: status values `ativo`/`inativo` are a known debt item — see [TD-011](memory/tech-debt.md).)
9. **Async operations go to the queue.** Never generate tickets or send emails synchronously in a controller.
10. **Every write endpoint needs a Form Request.** Never use `$request->input()` directly in a controller.
11. **All organizer-scoped queries must be explicitly scoped.** See [AD-009](memory/known-decisions.md).

---

## Key Domain Vocabulary

| Term | Meaning |
|------|---------|
| **Organizer** | A sports event company — the **tenant unit** of the platform |
| **Event** | A specific race/competition owned by an Organizer |
| **Category** | A race division within an Event (5K, 10K, gender/age group) |
| **TicketType** | A purchasable product for an Event (price, quota, sale window) — also called "Lote" |
| **Order** | A purchase transaction containing one or more OrderItems |
| **OrderItem** | One participant's registration within an Order |
| **Ticket** | The QR-code artifact generated after an Order is paid |
| **Payout** | How money flows from Mercado Pago to the Organizer (direct / indirect) |
| **Validation** | Scanning a QR code at event check-in to mark a Ticket as `used` |

Full glossary → [rules/business-domains.md § Business Glossary](rules/business-domains.md)
