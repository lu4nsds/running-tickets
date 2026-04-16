# memory/tech-debt.md — Technical Debt Register

> Last updated: 2026-04-16
> Priority = impact × risk if left unaddressed.
> How to fix each item → [../workflows/refactor-flow.md](../workflows/refactor-flow.md)
> Back to manifesto: [../CLAUDE.md](../CLAUDE.md)

---

## HIGH Priority

### TD-001 — Authorization Logic Inline in Controllers

**What:** Role checks, organizer membership checks, and resource ownership checks are written as `if` blocks directly inside controller methods, returning manual 403 responses.

**Where:** `api/app/Http/Controllers/Api/OrderController.php`, `TicketController.php`, and others.

**Why it matters:** Inline auth is untestable in isolation, silently skippable when a new method is added, and makes security audits impossible. Bugs here are security vulnerabilities.

**Target:** All resource authorization through `app/Policies/`. Controllers call only `$this->authorize('action', $model)`.

**Fix:**
```bash
php artisan make:policy OrderPolicy --model=Order
```
1. Move all `if (!user->canAccess...)` logic to the Policy
2. Register in `AuthServiceProvider::$policies`
3. Replace controller checks with `$this->authorize()`

**Policies to create:**
- `Order` → `OrderPolicy`
- `Ticket` → `TicketPolicy`
- `Event` → `EventPolicy`
- `Organizer` → `OrganizerPolicy`
- `TicketType` → `TicketTypePolicy`
- `Category` → `CategoryPolicy`

**Refactor pattern:** [Pattern 1 — Inline Auth → Policy](../workflows/refactor-flow.md)

---

### TD-002 — Absent Test Suite

**What:** No meaningful Pest Feature tests or Unit tests observed in the codebase.

**Where:** `api/tests/` — present but likely minimal.

**Why it matters:** Any refactor is high-risk without tests. The payment webhook and ticket generation pipeline are untested. A regression in the webhook handler results in lost orders and undelivered tickets.

**Target:** Pest Feature test coverage for all API endpoints. Unit tests for Services and model business methods.

**Immediate priority (highest business impact):**
1. `POST /api/orders` — order creation + quota check
2. `POST /api/webhooks/mercadopago` — payment webhook
3. `POST /api/auth/login` + `/register`
4. `POST /api/tickets/{code}/validate`
5. `GenerateOrderTicketsJob` — QR generation + email dispatch

**Fix:** Add a Pest Feature test before touching any existing endpoint in a refactor. Never refactor untested code without adding the test first.

---

### TD-003 — Missing/Thin Service Layer

**What:** Business logic (order creation, ticket quota check, payment orchestration, ticket generation) is distributed between controllers, jobs, and models without a clean service layer.

**Where:** `OrderController::store()`, `OrderController::processPayment()`, `GenerateOrderTicketsJob::handle()`.

**Why it matters:** Controllers become untestable (they depend on HTTP context). Logic is duplicated. Future complexity cannot be absorbed.

**Target services:**
- `OrderService` — create order, calculate total, cancel, validate quota
- `TicketService` — generate ticket record + QR SVG
- `MercadoPagoService` (already exists) — payment preference, webhook validation, credential check

**Fix:** Extract from `OrderController::store()` into `OrderService::create()` first (highest complexity). See [Pattern 2](../workflows/refactor-flow.md).

---

## MEDIUM Priority

### TD-004 — Frontend API Calls Not Centralized

**What:** Some Vue components make API calls directly via `axios` instead of using centralized `src/api/*.js` modules.

**Where:** `client/src/views/` and `admin/src/views/` — scattered.

**Why it matters:** Token injection not guaranteed on all calls. Error handling inconsistent. API base URL hardcoded in components (brittle on environment changes).

**Target:** All API calls through `src/api/*.js` modules, consumed via composables or stores.

**Fix:** Audit `<script setup>` blocks for `import axios`. Extract to `api/` module + composable. See [Pattern 4](../workflows/refactor-flow.md).

---

### TD-005 — Missing Indexes on Frequently Queried Columns

**What:** Some columns used in `WHERE` clauses may lack database indexes.

**Where:** `api/database/migrations/`

**Why it matters:** At scale (thousands of orders, events, tickets), full-table scans cause degraded response times on list endpoints and dashboards.

**Columns to verify (add index if missing):**
- `orders.buyer_email` — used to link guest orders on registration
- `orders.status` — used in dashboard aggregations
- `tickets.code` — UUID lookup on every QR scan — must have UNIQUE index
- `events.city`, `events.state` — used in public event search filters
- `events.date_start` — used in date range filter + sort

**Fix:** Create migration `add_missing_indexes_to_events_orders_tickets_tables`. Run `EXPLAIN` on slow queries to confirm.

---

### TD-006 — Inconsistent Enum Usage (Raw String Comparisons)

**What:** Some status comparisons use raw strings (`$order->status === 'paid'`) instead of the PHP backed Enums in `app/Enums/`.

**Where:** `api/app/` — controllers, services, observer conditions.

**Why it matters:** Raw string comparisons bypass type safety. IDE tooling cannot detect the reference. A renamed Enum value silently breaks comparisons.

**Target:** All status comparisons use Enum cases: `$order->status === OrderStatus::Paid`.

**Fix:**
1. Verify all models have the correct Enum cast in `$casts`
2. `grep -r "'paid'\|'active'\|'cancelled'\|'ativo'\|'inativo'" api/app/`
3. Replace with Enum case

**Enums to verify:** `OrderStatus` · `TicketStatus` · `EventStatus` · `OrganizerRole` · `UserRole`

See [Pattern 3](../workflows/refactor-flow.md).

---

### TD-007 — `participant_data` JSON Schema Undocumented and Unguarded

**What:** `OrderItem.participant_data` is a JSON blob. Its shape is only validated in `StoreOrderRequest`, with no model-level or DB-level enforcement.

**Where:** `api/app/Http/Requests/StoreOrderRequest.php`, `api/app/Models/OrderItem.php`

**Why it matters:** A future admin panel, CSV import, or new API version could write malformed participant data that bypasses the Form Request, silently corrupting records.

**Target:** Document the exact `participant_data` schema in [rules/business-domains.md](../rules/business-domains.md). Consider a `ParticipantData` value object (PHP class) that wraps reading/writing this JSON.

**Fix:** Document schema first. Create value object when the admin panel or bulk import feature is implemented.

---

## LOW Priority

### TD-008 — Large View Components (Frontend)

**What:** Some view components in `client/src/views/` and `admin/src/views/` mix data fetching, form logic, and display markup in a single large file.

**Why it matters:** Hard to review, hard to test, accumulates bugs faster.

**Target:** Views < ~150 lines, acting as thin orchestrators. Sub-components in `src/components/{domain}/`.

**Fix:** Extract during feature work — not as standalone refactors. Identify views > 200 lines. See [Pattern 5](../workflows/refactor-flow.md).

---

### TD-009 — No Soft Deletes on Orders and Tickets

**What:** `orders` and `tickets` tables have no `deleted_at` column (soft deletes).

**Why it matters:** Hard deletion of an order or ticket is irreversible. Financial and validation audit trails would be lost.

**Target:** Add `SoftDeletes` trait to `Order` and `Ticket` models, add `deleted_at` via migration.

**Fix:**
```php
// New migration
Schema::table('orders',  fn($t) => $t->softDeletes());
Schema::table('tickets', fn($t) => $t->softDeletes());
// Models: use SoftDeletes;
```

---

### TD-010 — No API Versioning Strategy

**What:** All routes are under `/api/` with no version prefix.

**Why it matters:** Future breaking API changes affect all clients simultaneously. Required when a mobile app or third-party integration is introduced.

**Target:** `/api/v1/` prefix when API stabilizes.

**Fix:** Not urgent. Add versioning when a mobile app or third-party integration requirement appears. See [AD-013](../memory/known-decisions.md).

---

### TD-011 — Portuguese Status Values in EventStatus Enum

**What:** `EventStatus` uses Portuguese values `'ativo'` and `'inativo'` instead of English `'active'` / `'inactive'`.

**Why it matters:** All other Enums use English values. Inconsistency causes confusion and violates the naming rule.

**Target:** Standardize to English in DB values and Enum cases. Portuguese stays in UI labels only.

**Fix (two-phase migration):**
1. Migration: UPDATE `events` SET `status = 'active'` WHERE `status = 'ativo'` (and `inactive`)
2. Update `EventStatus` Enum values
3. Update `EventStatus` comparisons in code
4. Update seeds and tests

**Risk:** High — requires data migration + code change simultaneously in production. Coordinate carefully.

---

## Resolved Items

| ID | Description | Resolved Date | PR |
|----|-------------|--------------|-----|
| — | — | — | — |

*When an item is fully resolved, move it here with the date and PR link.*
