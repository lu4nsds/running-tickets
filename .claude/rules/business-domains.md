# rules/business-domains.md — Business Domains

> Read this before touching any feature to understand the business context.
> Current code = source of business truth. This file describes WHAT the system does.
> Back to manifesto: [../CLAUDE.md](../CLAUDE.md)

---

## Platform Overview

**Running Tickets** is a B2B2C e-ticketing platform for sports events, operating in Brazil (BRL, Mercado Pago):

- **B2B:** Platform sells event management tools to Organizers
- **B2C:** Organizers sell event tickets to end users (athletes/participants)

---

## Domain Map

```
┌──────────────────────────────────────────────────────────────┐
│                     RUNNING TICKETS                          │
│                                                              │
│  ┌─────────────────┐     ┌────────────────────────────────┐  │
│  │ Identity & Auth │     │   Organizer Management         │  │
│  │  User · Role    │────▶│  Organizer · OrganizerUser     │  │
│  └─────────────────┘     └──────────────┬─────────────────┘  │
│                                         │                    │
│                          ┌──────────────▼─────────────────┐  │
│                          │   Event Catalog                │  │
│                          │  Event · Category · TicketType │  │
│                          └──────────────┬─────────────────┘  │
│                                         │                    │
│           ┌─────────────────────────────▼───────────────┐    │
│           │     Ticketing & Orders                      │    │
│           │     Order → OrderItem → Ticket              │    │
│           └─────────────────────┬───────────────────────┘    │
│                                 │                            │
│       ┌─────────────────────────▼──┐   ┌──────────────────┐  │
│       │ Payments                   │   │  Notifications   │  │
│       │ EventPayoutSetting + MP    │   │  Email via Resend│  │
│       └───────────────────────────┘   └──────────────────┘  │
│                                                              │
│  ┌───────────────────────────────────────────────────────┐   │
│  │  Ticket Validation — QR scan → mark Ticket as used   │   │
│  └───────────────────────────────────────────────────────┘   │
└──────────────────────────────────────────────────────────────┘
```

---

## Domain: Identity & Auth

**Models:** `User`, `Role`, pivot `user_roles`

**Key Rules:**
- Every registered user automatically receives the `user` global role
- `super_admin` role is assigned manually (database/artisan) — no UI for this
- A user can belong to multiple Organizers with different roles in each
- Guest checkout is allowed — no account required to purchase tickets (see [AD-005](../memory/known-decisions.md))
- Guest orders are linked to a user account when the buyer email matches at login/register
- Supported auth: email + password, Google OAuth (Socialite)
- Email verification: signed URL sent via Resend, required for full access

---

## Domain: Organizer Management

**Models:** `Organizer`, pivot `organizer_users`

**This is the tenant unit.** Each Organizer is fully isolated.

| Pivot Role | Permissions |
|-----------|------------|
| `admin` | Full access: configure payments, manage staff, view orders |
| `staff` | Limited: validate tickets, view events/orders (read-only) |

**Key Rules:**
- Super Admins create Organizers (no self-registration)
- Organizer Admins can add/remove staff
- One User can be `admin` of Org A and `staff` of Org B simultaneously
- Organizer fields: `name`, `document` (CNPJ/CPF), `email`, `phone`, full address, `status`

---

## Domain: Event Catalog

**Models:** `Event`, `Category`, `TicketType`

**Key Rules:**

**Event status flow:**
```
Created (status=inativo) → [Super Admin activates] → ativo → visible on /eventos
                         → [Super Admin deactivates] → inativo → hidden
```

Note: `ativo`/`inativo` are Portuguese status values — tracked as [TD-011](../memory/tech-debt.md).

- Event must have ≥ 1 Category and ≥ 1 TicketType to be usable
- `slug` is globally unique, derived from title, must not change after creation (SEO) — see [AD-004](../memory/known-decisions.md)
- Banners on S3, served via API proxy — see [AD-007](../memory/known-decisions.md)

**TicketType availability (isAvailableForPurchase):**
```
active = true
AND now() >= start_sale  (if set)
AND now() <= end_sale    (if set)
AND (quota is null OR sold_count < quota)
```

**Category fields:** `name`, `distance`, `gender`, `min_age`, `max_age`, `active`

---

## Domain: Ticketing & Orders

**Models:** `Order`, `OrderItem`, `Ticket`

**Order lifecycle:**
```
pending → paid      (Mercado Pago webhook confirms payment)
pending → cancelled (user cancels OR payment fails/expires)
paid    → refunded  (future feature — manual admin action)
```

**Order creation rules:**
- Event must be `ativo`
- Each item must reference an available `TicketType` (quota enforced)
- `buyer_email` = person paying (may differ from participants) — see [AD-010](../memory/known-decisions.md)
- `reference` = human-readable ID `ORD-YYYY-RANDOM` (not numeric ID) — see [AD-003](../memory/known-decisions.md)
- `total_cents` = sum of all TicketType `price_cents` in the order

**OrderItem (one participant):**
- `participant_data` JSON: `name`, `email`, `age`, `cpf` (+ custom fields per event) — see [AD-011](../memory/known-decisions.md)
- `category_id` nullable — which race division this participant is in
- `user_id` nullable — linked if participant is a registered user

**Ticket:**
- Created only after Order status = `paid` (async — see [AD-008](../memory/known-decisions.md))
- `code` = UUID — prevents enumeration — see [AD-002](../memory/known-decisions.md)
- QR = SVG at `storage/tickets/{code}.svg`
- Status: `active` → `used` (validated at event) | `cancelled` | `refunded`

---

## Domain: Payments

**Model:** `EventPayoutSetting`

**Gateway:** Mercado Pago only (current)

**Payout modes:**
- `direct` — Platform receives, transfers to organizer later
- `indirect` — Organizer receives directly via their own MP account

**Full payment flow:**
```
1. Frontend renders Mercado Pago Bricks (card tokenization / PIX)
2. POST /api/orders/{ref}/payment → backend creates MP payment preference
3. Mercado Pago processes payment asynchronously
4. POST /api/webhooks/mercadopago → verify signature → update Order status
5. If status = paid → queue GenerateOrderTicketsJob
```

**Supported payment methods:** Credit card (installments) · Debit card · PIX · Boleto

**Credential management:**
- Each event has its own MP credentials in `EventPayoutSetting.details` (JSON, treat as sensitive)
- Organizer admins configure at `/organizer/payment-settings`
- `MercadoPagoService::validateCredentials()` verifies token before saving

See [AD-006](../memory/known-decisions.md).

---

## Domain: Ticket Validation

**Actors:** Organizer staff or admin with the admin app (phone/tablet)

**Flow:**
```
1. Login to admin app → Validate Tickets → select event
2. Camera opens (html5-qrcode)
3. Scan QR code → sends ticket UUID to backend
4. POST /api/tickets/{code}/validate
5. Backend checks:
   a. Ticket exists
   b. Status = active
   c. Requesting user belongs to this event's organizer
6. Mark Ticket status = used, set used_at
7. UI shows green (valid) or red (already used / invalid / not found)
```

**Authorization:** Both `admin` and `staff` roles can validate. Super admins can validate any ticket.

---

## Domain: Notifications

**Mail classes in `api/app/Mail/`:**

| Mail | Trigger | Recipient |
|------|---------|-----------|
| `VerifyEmailMail` | User registration | New user |
| `PasswordResetMail` | Forgot password | User |
| `OrderPaidMail` | Order paid (async job) | `buyer_email` — all tickets |
| `ParticipantTicketMail` | Order paid (async job) | Each `participant.email` — individual ticket |

**Email driver:** Resend (`MAIL_*` env vars) — see [AD-014](../memory/known-decisions.md).

---

## Entity Relationship Summary

```
User (1) ──── (M) user_roles (M) ──── (1) Role
User (1) ──── (M) organizer_users (M) ──── (1) Organizer
Organizer (1) ──── (M) Event
Event (1) ──── (M) Category
Event (1) ──── (M) TicketType
Event (1) ──── (M) Order
Event (1) ──── (M) EventPayoutSetting
Order (1) ──── (M) OrderItem
OrderItem (1) ──── (1) Ticket
TicketType (1) ──── (M) OrderItem
Category (1) ──── (M) OrderItem
```

---

## Business Glossary

| Portuguese (UI / routes) | English (code / DB) | Meaning |
|--------------------------|---------------------|---------|
| Organizador | Organizer | Company that creates and manages events |
| Evento | Event | A specific sports competition |
| Categoria | Category | A race division (distance/gender/age group) |
| Ingresso | Ticket | The QR-code artifact for event entry |
| Tipo de Ingresso / Lote | TicketType | A purchasable product with price, quota, sale window |
| Pedido | Order | A purchase transaction |
| Participante | Participant | A person registered to compete |
| Validação | Validation | Check-in QR scan at the event |
| Repasse | Payout | Payment transfer from platform to organizer |
| Inscrição | Registration | The act of purchasing a ticket |
| Ficha | Entry/Record | Participant's registration data |

**Language rule:** Portuguese is acceptable in UI strings and route paths (`/eventos`, `/entrar`). All code identifiers, DB columns, file names, and Enum values must be English.
