# rules/database-standards.md — Database Standards

> Target: MySQL 8.4 + Laravel Eloquent best practices.
> All migrations must be reversible. All indexes must be intentional.
> Back to manifesto: [../CLAUDE.md](../CLAUDE.md)

---

## Migration Naming

| Operation | Pattern | Example |
|-----------|---------|---------|
| Create table | `create_{table}_table` | `2026_01_10_create_orders_table` |
| Add column | `add_{column}_to_{table}_table` | `2026_03_01_add_verified_at_to_users_table` |
| Rename column | `rename_{old}_to_{new}_in_{table}_table` | |
| Drop column | `remove_{column}_from_{table}_table` | |
| Add index | `add_{index}_index_to_{table}_table` | |
| Create pivot | `create_{table1}_{table2}_table` (alphabetical) | `create_organizer_users_table` |

**Rules:**
- Never modify a migration that has run in production — always create a new one
- Always implement `down()` that perfectly reverses `up()`
- Timestamps document when a schema change was introduced

---

## Table Naming

- **Snake_case, plural noun**: `orders`, `order_items`, `ticket_types`
- **Pivot tables**: both nouns alphabetical: `organizer_users`, `user_roles`
- **Never abbreviate**: `order_items` not `ord_items`

| Entity | Table |
|--------|-------|
| User | `users` |
| Role | `roles` |
| User ↔ Role | `user_roles` |
| Organizer | `organizers` |
| Organizer ↔ User | `organizer_users` |
| Event | `events` |
| Category | `categories` |
| TicketType | `ticket_types` |
| Order | `orders` |
| OrderItem | `order_items` |
| Ticket | `tickets` |
| EventPayoutSetting | `event_payout_settings` |

---

## Column Naming

- **Snake_case** for all columns
- **Foreign keys**: `{singular_model_name}_id` → `organizer_id`, `event_id`
- **Timestamps**: `created_at`, `updated_at` (Laravel default), `deleted_at` (soft deletes)
- **Status fields**: `VARCHAR(20) NOT NULL` + PHP backed Enum (never MySQL ENUM)
- **Boolean flags**: `TINYINT(1)` / `boolean()` → `active`, `verified`
- **JSON bags**: `_data` or `_meta` suffix → `participant_data`, `metadata`
- **UUIDs**: `CHAR(36) UNIQUE NOT NULL` → `tickets.code`

### Money — Always Integers (centavos)

```php
// Migration
$table->unsignedInteger('price_cents')->comment('Price in BRL centavos');
$table->char('currency', 3)->default('BRL');

// Model cast
protected $casts = ['price_cents' => 'integer'];
```

**Never:**
```php
$table->decimal('price', 10, 2);  // floating-point rounding errors
```

See [AD-001](../memory/known-decisions.md) for rationale.

---

## Index Strategy

### Always Index

| Case | How |
|------|-----|
| Primary key | `$table->id()` (automatic) |
| Foreign keys | `constrained()` adds index automatically |
| Business-unique fields | `->unique()` on `slug`, `email`, `reference`, `code` |
| `WHERE` filter columns | `$table->index('status')` |
| `ORDER BY` columns | `$table->index('date_start')` |
| Composite filters | `$table->index(['city', 'state'])` — most selective first |

### Never Index

- `description`, `notes`, `address` — free text, never queried by value
- Columns that are only `SELECT`ed, never filtered

### Reference Migration (Target Quality)

```php
Schema::create('events', function (Blueprint $table) {
    $table->id();
    $table->foreignId('organizer_id')->constrained()->cascadeOnDelete();
    $table->string('slug')->unique();
    $table->string('title');
    $table->string('status', 20)->default('inativo');  // TD-011: migrate to 'inactive'
    $table->string('city', 100)->nullable();
    $table->string('state', 2)->nullable();
    $table->dateTime('date_start');
    $table->timestamps();

    $table->index('status');
    $table->index(['city', 'state']);
    $table->index('date_start');
});
```

```php
Schema::create('orders', function (Blueprint $table) {
    $table->id();
    $table->foreignId('event_id')->constrained()->cascadeOnDelete();
    $table->foreignId('organizer_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
    $table->string('reference', 30)->unique();       // ORD-YYYY-RANDOM — see AD-003
    $table->string('status', 20)->default('pending');
    $table->string('buyer_email');
    $table->unsignedInteger('total_cents');
    $table->char('currency', 3)->default('BRL');
    $table->timestamps();

    $table->index('status');
    $table->index('buyer_email');  // link guest orders on login
});
```

```php
// Tickets — full reference migration
public function up(): void
{
    Schema::create('tickets', function (Blueprint $table) {
        $table->id();
        $table->foreignId('order_item_id')->unique()->constrained()->cascadeOnDelete(); // 1:1
        $table->char('code', 36)->unique();      // UUID — see AD-002
        $table->string('qr_path')->nullable();
        $table->string('status', 20)->default('active');
        $table->timestamp('issued_at')->nullable();
        $table->timestamps();

        $table->index('status');
    });
}

public function down(): void
{
    Schema::dropIfExists('tickets');
}
```

---

## Pivot Tables with Extra Data

```php
Schema::create('organizer_users', function (Blueprint $table) {
    $table->id();
    $table->foreignId('organizer_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('role', 20)->default('staff');   // 'admin' | 'staff'
    $table->timestamps();

    $table->unique(['organizer_id', 'user_id']);    // prevent duplicate membership
});
```

**Eloquent:**
```php
public function organizers(): BelongsToMany
{
    return $this->belongsToMany(Organizer::class, 'organizer_users')
                ->withPivot('role')
                ->withTimestamps();
}

$user->organizers->first()->pivot->role; // access pivot data
```

---

## JSON Columns

Use JSON for flexible, **non-queryable** data only.

**Acceptable in this project:**

| Column | Table | Contents |
|--------|-------|----------|
| `participant_data` | `order_items` | name, email, age, CPF of one participant |
| `metadata` | `orders` | payment gateway response data |
| `details` | `event_payout_settings` | Mercado Pago credentials (treat as sensitive) |
| `attributes` | `ticket_types` | optional extensible fields |
| `meta` | `events` | optional extensible metadata |

**Cast in model:**
```php
protected $casts = [
    'participant_data' => 'array',
    'metadata'         => 'array',
];
```

**Never use JSON for:**
- Data you need to `WHERE` on — extract to a real column instead
- Status fields — use VARCHAR + PHP Enum

---

## Soft Deletes Policy

**Default: do not add `SoftDeletes` unless necessary.**

Add soft deletes only when:
1. Records must be preserved for auditing (financial / legal trail)
2. The record has FK references that block hard deletion
3. Business requires restore capability

| Table | Should Soft Delete? | Status |
|-------|-------------------|--------|
| `orders` | Yes — payment audit trail | Missing — see [TD-009](../memory/tech-debt.md) |
| `tickets` | Yes — validation audit trail | Missing — see [TD-009](../memory/tech-debt.md) |
| `categories` | No | ✓ |
| `ticket_types` | Only if orders reference them | Conditional |

---

## Enum vs. String vs. MySQL ENUM

| Use Case | Use | Example |
|----------|-----|---------|
| Status with multiple states | `VARCHAR(20) NOT NULL` + PHP backed Enum | `orders.status` |
| Boolean flag | `boolean()` / `TINYINT(1)` | `categories.active` |
| MySQL ENUM | **Avoid** | — |

**Why avoid MySQL ENUM:** Adding a new value requires `ALTER TABLE` (expensive). PHP Enums are more flexible and equally type-safe. Enforce constraints at the application layer.

---

## Do / Don't — Quick Reference

**Do:**
```php
$table->foreignId('event_id')->constrained()->cascadeOnDelete();
$table->unsignedInteger('price_cents');
$table->string('status', 20)->default('pending');
$table->char('code', 36)->unique();
$table->json('metadata')->nullable();
$table->index(['organizer_id', 'status']);
```

**Don't:**
```php
$table->decimal('price', 10, 2);         // floating-point money
$table->enum('status', ['a', 'b']);       // MySQL ENUM
$table->integer('event_id');              // FK without constrained()
$table->text('short_name');              // text() for short strings
// missing index on FK columns            // always index _id columns
```
