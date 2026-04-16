# memory/known-decisions.md — Architectural Decisions

> These decisions have been made and should not be re-debated without strong reason.
> Each entry: WHAT was decided · WHY · CONSEQUENCE.
> Back to manifesto: [../CLAUDE.md](../CLAUDE.md)

---

## AD-001 — Money Stored as Integer Centavos

**Decision:** All monetary values stored as `UNSIGNED INT` centavos (BRL smallest unit). Columns use `_cents` suffix.

**Why:** Floating-point arithmetic on `DECIMAL` produces rounding errors when aggregating large transaction volumes. Integer arithmetic is exact.

**Consequence:**
- Display: divide by 100 → `R$ 4990` → `R$ 49,90`
- Input: multiply by 100 before storing
- Columns: `ticket_types.price_cents`, `orders.total_cents`
- **Never use** `DECIMAL` for money in this project

---

## AD-002 — Ticket Codes Are UUIDs

**Decision:** `Ticket.code` is a UUID v4, not a sequential integer.

**Why:** Sequential IDs allow enumeration attacks (scan ticket/1, ticket/2…). UUIDs are unguessable.

**Consequence:**
- QR codes encode the UUID string (not the DB id)
- URLs use `code`, not `id`
- `tickets.code` has a UNIQUE index
- `Ticket::generateCode()` → `Str::uuid()`

---

## AD-003 — Orders Use Reference Codes (Not Numeric IDs)

**Decision:** `Order` exposes a `reference` field (`ORD-YYYY-XXXXXXXX`) to clients. Route model binding key = `reference`.

**Why:** Hides total order volume from competitors. More readable in emails and support interactions.

**Consequence:**
- All frontend routes use `ORD-2026-ABC123` (not numeric id)
- `Order::getRouteKeyName()` returns `'reference'`
- `orders.reference` has a UNIQUE index

---

## AD-004 — Events Use Slugs in Public URLs

**Decision:** Events are identified in public URLs by `slug`, not numeric `id`. Example: `/eventos/maratona-sao-paulo-2026`.

**Why:** SEO-friendly URLs improve search ranking. Hides internal IDs.

**Consequence:**
- `events.slug` must be globally unique
- Slug generation must handle duplicates (append `-2`, `-3`)
- **Changing an event's title must not change its slug** (would break indexed URLs, existing links)
- `Event::getRouteKeyName()` likely returns `'slug'` for public routes

---

## AD-005 — Guest Checkout Supported

**Decision:** Tickets can be purchased without a user account. `Order.user_id` is nullable.

**Why:** Friction reduction increases conversion. Many sports buyers purchase once and don't need a persistent account.

**Consequence:**
- `OptionalAuth` middleware on order and payment endpoints
- `buyer_email` used to identify buyer (may differ from participants)
- On registration/login: past orders matched by `buyer_email` are linked retroactively
- `orders.user_id` nullable by design — do not make it required

---

## AD-006 — Per-Event Mercado Pago Credentials

**Decision:** Each event can have its own MP access token stored in `EventPayoutSetting.details` (JSON).

**Why:** Different organizers have different financial arrangements. Some want direct control (indirect mode), others prefer platform-mediated transfers (direct mode).

**Consequence:**
- `EventPayoutSetting.details` contains sensitive credentials — treat as secrets (encrypted at rest in production)
- Platform also has its own MP credentials in `.env` (for direct payout mode)
- `MercadoPagoService::validateCredentials()` must be called before saving new credentials

---

## AD-007 — S3 Storage Served Via API Proxy

**Decision:** Event banners stored on S3 are served through `GET /api/storage/{path}`, not directly from S3 URLs.

**Why:** Avoids CORS issues. Keeps S3 bucket private (no public ACL required). Enables future access control on asset delivery.

**Consequence:**
- Frontend `<img :src>` always uses the API proxy URL
- `Event::getBannerFullUrlAttribute()` accessor returns the proxy URL, never the raw S3 key
- All new file storage must follow the same proxy pattern
- This adds API server load for image requests (acceptable for current scale)

---

## AD-008 — Ticket Generation Is Asynchronous (Queue)

**Decision:** After an order is paid, ticket generation (QR codes + emails) happens asynchronously in a Redis queue worker via `GenerateOrderTicketsJob`.

**Why:** Mercado Pago webhooks expect a response in < 5 seconds. QR generation + file storage + multiple emails exceed this limit when done synchronously, causing MP to retry the webhook (leading to duplicate processing).

**Consequence:**
- Small delay (seconds) between payment confirmation and ticket delivery
- `GenerateOrderTicketsJob` must be **idempotent** (safe to retry)
- Redis is a hard infrastructure requirement (queue + cache)
- Webhook handler must complete fast (validate signature, update status, return 200 immediately)

---

## AD-009 — Multi-Tenancy via Application-Level Scoping

**Decision:** `Organizer` is the tenant unit. Tenancy enforced in application middleware + query scoping. No database-level row isolation (no separate schemas/databases).

**Why:** DB-level isolation would require significant infrastructure complexity disproportionate to current scale. Application-level scoping with `EnsureOrganizerAccess` middleware + explicit query filters is sufficient.

**Consequence:**
- Every endpoint returning organizer-scoped data **must** explicitly scope the query
- `EnsureOrganizerAccess` middleware enforces the boundary at the route group level
- New endpoints that touch organizer data must add `->where('organizer_id', $organizerId)` or equivalent
- **Super Admins bypass all tenant scoping** — they see all data

---

## AD-010 — Separate `buyer_email` and Participant `email`

**Decision:** `Order.buyer_email` = person paying. `OrderItem.participant_data.email` = person competing. These are separate fields and can differ.

**Why:** One payer may register multiple participants (family member, corporate registration). Buyer gets the full order confirmation. Each participant gets only their own ticket.

**Consequence:**
- Two separate emails dispatched by `GenerateOrderTicketsJob`:
  - `OrderPaidMail` → `buyer_email` (all tickets in order)
  - `ParticipantTicketMail` → each `participant_data.email` (individual ticket)
- Do not conflate buyer identity with participant identity anywhere in the codebase

---

## AD-011 — OrderItem Stores Participant Data as JSON

**Decision:** Participant personal data (name, CPF, age, custom event fields) stored as JSON in `OrderItem.participant_data`.

**Why:** Different events require different participant fields (some need CPF, others require medical clearance, others custom questions). A flexible JSON field avoids per-event schema migrations.

**Consequence:**
- Individual participant fields (e.g. `cpf`) **cannot be indexed or queried efficiently**
- If CPF lookup or CSV export by field becomes necessary, the field must be extracted to a real column with a migration
- Validation enforced in `StoreOrderRequest` (Form Request) — this is the schema contract
- See [TD-007](tech-debt.md) — schema needs documentation as a formal contract

---

## AD-012 — Two-Tier Organizer Role Hierarchy (admin / staff)

**Decision:** Organizer team members have role `admin` or `staff` in the `organizer_users` pivot.

**Why:** Event check-in volunteers should not access financial data or payment settings. Minimum privilege principle.

**Consequence:**
- Payment settings configuration requires `admin` role
- Ticket validation (`POST /api/tickets/{code}/validate`) allowed for both `admin` and `staff`
- Super admins bypass this check entirely
- Adding finer permission granularity in the future requires extending this pivot

---

## AD-013 — No API Versioning (Current)

**Decision:** All routes under `/api/` with no version prefix.

**Why:** Single known consumer (two frontend apps). Versioning overhead is premature at this stage.

**Consequence:**
- Breaking API changes must be coordinated with frontend deployments
- **Revisit when:** a mobile app or third-party integration is introduced
- See [TD-010](tech-debt.md) for the debt entry

---

## AD-014 — Resend for Transactional Email

**Decision:** Transactional emails sent via [Resend](https://resend.com) (`resend/resend-php` package + Laravel mail driver).

**Why:** High deliverability, generous free tier, simple SDK, easy Laravel integration.

**Consequence:**
- `MAIL_*` env vars point to Resend SMTP/API
- Changing email providers requires only updating `MAIL_*` vars (driver-agnostic via Laravel Mail)
- All email templates are `app/Mail/*.php` Mailable classes with Blade views
