# rules/architecture.md — System Architecture

> Read before modifying any cross-cutting concern (auth, middleware, routing, queues, storage).
> Back to manifesto: [../CLAUDE.md](../CLAUDE.md)

---

## System Topology

```
┌─────────────────────────────────────────────────────────────────┐
│                         Browser Clients                         │
├──────────────────────┬──────────────────────────────────────────┤
│  client/ (port 5173) │          admin/ (port 5174)              │
│  Public ticket shop  │  Organizer + Super Admin dashboard        │
└──────────┬───────────┴────────────────────┬─────────────────────┘
           │  HTTP/JSON (Sanctum Bearer)     │
           ▼                                ▼
┌─────────────────────────────────────────────────────────────────┐
│                      Nginx (port 80)                            │
│                   Reverse proxy / API gateway                   │
└─────────────────────────────┬───────────────────────────────────┘
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                  api/ — Laravel 12 Application                  │
│   Routes → Middleware → Controller → Service → Model → DB       │
│   Async:  Observer → Queue Job → Email / QR generation          │
└──────┬──────────────────────────────────────┬───────────────────┘
       ▼                                      ▼
┌──────────────┐                    ┌─────────────────┐
│  MySQL 8.4   │                    │  Redis           │
│  (data)      │                    │  (cache + queue) │
└──────────────┘                    └────────┬────────┘
                                             │
                              ┌──────────────┴──────────────┐
                              ▼                             ▼
                    ┌─────────────────┐          ┌─────────────────┐
                    │  Queue Worker   │          │   Scheduler     │
                    │  (async jobs)   │          │  (cron tasks)   │
                    └─────────────────┘          └─────────────────┘

External: AWS S3 · Mercado Pago · Resend · Google OAuth
```

---

## Backend Layer Responsibilities

| Layer | Path | Rule |
|-------|------|------|
| Routes | `routes/api.php` | URL → Controller mapping + middleware groups |
| Middleware | `app/Http/Middleware/` | Coarse auth (route groups only) |
| Controllers | `app/Http/Controllers/Api/` | **HTTP only** — receive, delegate, respond |
| Form Requests | `app/Http/Requests/` | Input validation + route-level authorization |
| Policies | `app/Policies/` | Per-resource authorization — **never inline** |
| Services | `app/Services/` | Business logic + third-party integrations |
| Models | `app/Models/` | Eloquent — relationships, scopes, business rule methods |
| Observers | `app/Observers/` | Side effects on model lifecycle events |
| Jobs | `app/Jobs/` | Async queue work |
| Resources | `app/Http/Resources/` | JSON response shaping |
| Enums | `app/Enums/` | Type-safe status constants |

---

## Request Lifecycle

```
HTTP Request
  ↓ Nginx proxy pass → Laravel FPM
  ↓ Global Middleware (CORS, TrustProxies)
  ↓ Route matching (routes/api.php)
  ↓ Route Middleware (auth:sanctum | EnsureSuperAdmin | EnsureOrganizerAccess | OptionalAuth)
  ↓ Form Request authorize() + rules()   ← 422 if invalid
  ↓ Controller method
  ↓ Service (business logic)
  ↓ Eloquent Model → MySQL
  ↓ API Resource (transforms data)
  ↓ JsonResponse
```

---

## Authentication Flows

### Password Login
```
POST /api/auth/login {email, password}
  → Auth::attempt → create Sanctum token
  → Response: { user, token, organizers }
  → Client stores token in localStorage
  → All subsequent requests: Authorization: Bearer {token}
```

### Google OAuth
```
GET /api/auth/google
  → Socialite redirect
  → Google authenticates user
  → GET /api/auth/google/callback
  → Laravel: find-or-create User by google_id/email
  → Create Sanctum token → redirect to frontend with token in query param
  → Client stores token, calls GET /api/auth/me
```

### Guest Orders (OptionalAuth)
- Orders/payment endpoints attempt Sanctum auth but allow unauthenticated requests
- On guest registration/login: past orders matched by `buyer_email` are linked to account
- `orders.user_id` is nullable by design — see [AD-005](../memory/known-decisions.md)

---

## Queue / Ticket Generation Pipeline

```
POST /webhooks/mercadopago
  ↓ WebhookController validates MP signature
  ↓ Order::update(['status' => OrderStatus::Paid])
  ↓ OrderObserver::updated()
      - Cache::tags('dashboard')->flush()
      - GenerateOrderTicketsJob::dispatch($order) → Redis queue
  ↓ [Async — Queue Worker]
  GenerateOrderTicketsJob::handle()
      For each OrderItem:
        1. Create Ticket record (UUID code)
        2. Generate QR SVG (simplesoftwareio/simple-qrcode, 300×300, errorCorrection H)
        3. Storage::put("tickets/{code}.svg")
      Send OrderPaidMail → buyer_email (all tickets)
      Send ParticipantTicketMail → each participant.email (individual ticket)
```

**Queue config:** Redis driver · `default` queue · 3 retries · 60s timeout

**Why async?** Webhook must respond to Mercado Pago in < 5s. Sync processing causes timeouts and duplicate webhook retries. See [AD-008](../memory/known-decisions.md).

---

## Multi-Tenancy Model

**Tenant unit = Organizer.** Each Organizer owns:
- Events → Categories → TicketTypes
- Orders (scoped by `organizer_id`)
- Team members (`organizer_users` pivot: role `admin` | `staff`)
- Payment credentials (`EventPayoutSetting`)

**Enforcement is application-level (middleware + query scoping), not DB-level.**

```
EnsureOrganizerAccess:
  Super Admin → pass through (no scope)
  Organizer member → scope to their organizer via organizer_id or event.organizer_id
  Otherwise → 403
```

No data leaks between organizers if every organizer-scoped query is explicitly filtered.
**Any new endpoint returning organizer data must add scoping.** See [AD-009](../memory/known-decisions.md).

---

## Authorization Model (Three Layers)

```
1. Route Middleware (coarse — applies to entire route group)
   auth:sanctum              → must be authenticated
   EnsureSuperAdmin          → must have super_admin role
   EnsureOrganizerAccess     → must belong to organizer or be super admin

2. Policy (per resource — fine-grained ownership)
   app/Policies/{Model}Policy
   Called via $this->authorize('action', $resource) in controller
   ← THIS IS THE TARGET. Currently partially missing (TD-001).

3. Form Request authorize() (route-level)
   "Is this type of user allowed to hit this endpoint at all?"
   Not for resource ownership checks — that's Policies.
```

---

## Storage Proxy Pattern

```
Frontend <img :src="event.banner_url">
   ↓ banner_url = https://api.domain/api/storage/banners/xyz.jpg
   ↓ GET /api/storage/{path}
   ↓ S3::get(path) → stream response
```

- S3 bucket is private (no public ACL)
- `Event::getBannerFullUrlAttribute()` returns the proxy URL, not the raw S3 key
- Frontend must **never** construct direct S3 URLs — always use the accessor

See [AD-007](../memory/known-decisions.md).

---

## API Route Map

```
/api/
├── (public — no auth)
│   ├── GET  /health
│   ├── POST /auth/register · /auth/login
│   ├── GET  /auth/google[/callback]
│   ├── POST /password/forgot · /password/reset
│   ├── GET  /email/verify/{id}/{hash}
│   ├── GET  /storage/{path}                     ← S3 proxy
│   ├── GET  /events · /events/{slug}
│   ├── GET  /events/cities · /events/states
│   ├── GET  /events/{event}/categories
│   ├── POST /orders                             ← OptionalAuth
│   ├── POST /orders/{ref}/payment               ← OptionalAuth
│   ├── GET  /orders/{ref}/status                ← OptionalAuth
│   └── POST /webhooks/mercadopago
│
├── (auth:sanctum)
│   ├── POST /auth/logout
│   ├── GET  /auth/me
│   ├── GET  /orders · /orders/{ref}
│   ├── POST /orders/{ref}/cancel
│   ├── GET  /tickets · /tickets/{code}
│   ├── GET  /tickets/{code}/qr
│   └── POST /tickets/{code}/validate
│
├── /admin (EnsureSuperAdmin)
│   ├── GET  /dashboard · /organizers/{id}/dashboard
│   ├── apiResource: organizers (CRUD)
│   ├── apiResource: events (CRUD)
│   └── nested: events.categories · events.ticket-types
│
└── /organizer (EnsureOrganizerAccess)
    ├── GET  /dashboard · /events/{event}/dashboard
    ├── GET  /events[/{event}]   ← read-only
    ├── GET  /payment-settings
    └── GET/PUT /events/{event}/payout[/validate]
```

---

## Key Package Inventory

| Package | Purpose |
|---------|---------|
| `laravel/sanctum` | Bearer token auth |
| `laravel/socialite` | Google OAuth |
| `mercadopago/dx-php` | Payment SDK |
| `league/flysystem-aws-s3-v3` | S3 storage driver |
| `simplesoftwareio/simple-qrcode` | QR SVG generation |
| `barryvdh/laravel-dompdf` | PDF generation (optional) |
| `resend/resend-php` | Transactional email |
| `vue` 3 + `pinia` + `vue-router` 4 | SPA framework |
| `apexcharts` + `vue3-apexcharts` | Admin charts |
| `html5-qrcode` | Browser camera QR scanner (admin) |
