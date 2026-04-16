# workflows/refactor-flow.md — Refactoring Guide

> Use when improving existing code without changing behavior.
> Goal: converge toward standards in `rules/` without breaking anything.
> Back to manifesto: [../CLAUDE.md](../CLAUDE.md)

---

## Core Principle: Safe, Incremental Change

```
1. READ     — understand the code fully before touching it
2. TEST     — write tests to anchor current behavior
3. REFACTOR — make the single targeted change
4. VERIFY   — run tests + manual browser test of the feature
5. COMMIT   — one logical change per commit (refactor: prefix)
```

**Never in the same PR:**
- Refactor + new feature
- Multiple refactor patterns at once
- Code change + route/column rename

---

## Priority Order

Check [memory/tech-debt.md](../memory/tech-debt.md) for the current registered list. General priority:

| Priority | Pattern | Risk | Impact |
|----------|---------|------|--------|
| HIGH | Inline auth → Policy | Low | High (security + testability) |
| HIGH | Add missing tests | Low | High (enables safe future refactors) |
| HIGH | Controller logic → Service | Medium | High (testability) |
| MEDIUM | Raw string status → Enum comparison | Low | Medium |
| MEDIUM | Direct axios in component → api/ module | Low | Medium |
| MEDIUM | Large view → extracted components | Low | Low-Medium |
| LOW | Column/value naming (PT → EN) | High (migration needed) | Low |

---

## Pattern 1: Inline Authorization → Policy

**Smell:**
```php
// BAD — auth check inside controller method
public function show(Order $order): JsonResponse
{
    if (
        $request->user()->id !== $order->user_id &&
        !$request->user()->isSuperAdmin() &&
        !$request->user()->canAccessOrganizer($order->organizer_id)
    ) {
        return response()->json(['message' => 'Forbidden'], 403);
    }
    return new JsonResponse(new OrderResource($order));
}
```

**Target:**
```php
// GOOD
public function show(Order $order): JsonResponse
{
    $this->authorize('view', $order);
    return new JsonResponse(new OrderResource($order));
}
```

**Steps:**
1. `php artisan make:policy OrderPolicy --model=Order`
2. Copy the conditional into `OrderPolicy::view()`
3. Register in `AuthServiceProvider::$policies`
4. Replace inline check with `$this->authorize('view', $order)`
5. Run tests; verify 403 still returned for unauthorized users

**Do for:** `Order` · `Ticket` · `Event` · `Organizer` · `TicketType` · `Category` — see [TD-001](../memory/tech-debt.md)

---

## Pattern 2: Controller Business Logic → Service

**Smell:**
```php
// BAD — 60+ lines of business logic in controller
public function store(StoreOrderRequest $request): JsonResponse
{
    $event = Event::findOrFail($request->event_id);
    if ($event->status !== 'ativo') {
        return response()->json(['error' => 'Event not active'], 422);
    }
    foreach ($request->items as $item) {
        $ticketType = TicketType::find($item['ticket_type_id']);
        if (!$ticketType->isAvailableForPurchase()) {
            return response()->json(['error' => 'Sold out'], 422);
        }
    }
    // ... 40 more lines
}
```

**Target:**
```php
// GOOD — controller delegates entirely
public function store(StoreOrderRequest $request): JsonResponse
{
    $order = $this->orderService->create($request->validated());
    return new JsonResponse(new OrderResource($order), 201);
}
```

**Steps:**
1. Create `app/Services/OrderService.php`
2. Move all business logic from controller into `OrderService::create()`
3. Inject `OrderService` into the controller's constructor
4. Write a unit test for `OrderService::create()` directly
5. Verify controller test still passes (behavior unchanged)

**Target services:** `OrderService` · `TicketService` · `MercadoPagoService` — see [TD-003](../memory/tech-debt.md)

---

## Pattern 3: Raw String Status → Enum Comparison

**Smell:**
```php
if ($order->status === 'paid') { ... }        // BAD
if ($ticket->status === 'active') { ... }     // BAD
```

**Target:**
```php
if ($order->status === OrderStatus::Paid) { ... }      // GOOD
if ($ticket->status === TicketStatus::Active) { ... }  // GOOD
```

**Steps:**
1. Verify Enum exists (`app/Enums/OrderStatus.php`)
2. Verify model cast: `'status' => OrderStatus::class` in `$casts`
3. Find occurrences: `grep -r "'paid'" api/app/`
4. Replace raw strings with Enum cases
5. Run tests

**Enums to complete:** `OrderStatus` · `TicketStatus` · `EventStatus` · `OrganizerRole` · `UserRole` — see [TD-006](../memory/tech-debt.md)

---

## Pattern 4: Direct Axios in Component → API Module

**Smell:**
```vue
<!-- BAD — axios called directly in component -->
<script setup>
import axios from 'axios'
const events = ref([])
onMounted(async () => {
  const resp = await axios.get('/api/events')
  events.value = resp.data.data
})
</script>
```

**Target:**
```vue
<!-- GOOD — uses centralized API module -->
<script setup>
import { eventsApi } from '@/api/events'
const events    = ref([])
const isLoading = ref(false)
onMounted(async () => {
  isLoading.value = true
  events.value = await eventsApi.list()
  isLoading.value = false
})
</script>
```

**Steps:**
1. Ensure `src/api/axios.js` single instance exists with interceptors
2. Create/extend `src/api/events.js` with the needed function
3. Replace `import axios` in the component
4. Verify the auth token interceptor still applies to the call

See [TD-004](../memory/tech-debt.md).

---

## Pattern 5: Repeated Query Conditions → Eloquent Scope

**Smell:**
```php
// BAD — same condition repeated in multiple places
$orders = Order::where('organizer_id', $organizerId)->where('status', 'paid')->get();
```

**Target:**
```php
// In Order model:
public function scopeForOrganizer(Builder $query, int $organizerId): void
{
    $query->where('organizer_id', $organizerId);
}
public function scopePaid(Builder $query): void
{
    $query->where('status', OrderStatus::Paid);
}

// Usage:
$orders = Order::forOrganizer($organizerId)->paid()->get();
```

**Steps:**
1. Identify repeated `->where()` patterns across controllers/services
2. Add `scopeX()` methods on the Model
3. Replace all occurrences
4. Verify no behavioral change

---

## Pattern 6: Large View → Extracted Sub-Components

**Smell:** A single `.vue` file > 200 lines mixing fetching + display + form logic.

**Target:** View < 150 lines, thin orchestrator; logic extracted to sub-components or composables.

**Before:**
```
EventsView.vue (350 lines)
  — filter form markup + state
  — event list + pagination
  — fetch logic
```

**After:**
```
EventsView.vue (60 lines)   ← thin orchestrator
  EventFilters.vue          ← filter form + filter state
  EventList.vue
    EventCard.vue
  BasePagination.vue
composables/useEventFilters.js ← fetch + URL sync logic
```

**Steps:**
1. Identify the logical sections (filter, list, modal, pagination)
2. Extract each to its own component in `src/components/{domain}/`
3. Pass data via props, emit events back up
4. Move fetch logic to a composable if reused elsewhere

---

## Pattern 7: `$guarded = []` → Explicit `$fillable`

**Smell:**
```php
protected $guarded = [];  // allows mass assignment of any field
```

**Target:**
```php
protected $fillable = [
    'event_id', 'organizer_id', 'user_id', 'reference',
    'total_cents', 'currency', 'status', 'buyer_email',
];
```

**Steps:**
1. Read the table migration to find all columns
2. List columns that should be mass-assignable (exclude: `id`, `created_at`, `updated_at`, system fields)
3. Replace `$guarded` with explicit `$fillable`
4. Run tests

---

## Safety Rules

1. **Write tests first.** If no tests exist, write them before refactoring. Tests are the regression anchor.
2. **One pattern per PR.** Never mix "auth → Policy" with "controller → Service".
3. **Never rename public API routes.** Clients depend on them.
4. **Never rename DB columns without a migration plan:**
   - Phase 1: add new column, dual-write
   - Phase 2: migrate data, drop old column
5. **Read before deleting.** Use `git log` and `grep` to confirm code is unreachable.
6. **Manual browser test after every refactor.** Golden path + one failure case.

---

## Commit Convention for Refactors

```
refactor: move order creation logic from controller to OrderService
refactor: replace inline auth checks with OrderPolicy
refactor: add Eloquent scopes for Order filtering
refactor: extract EventFilters component from EventsView
```

Do **not** mix `refactor:` with `feat:` or `fix:` in the same commit.

---

## Definition of Done

A refactor is complete when:
- [ ] All existing tests pass
- [ ] Feature works identically in the browser (manual golden path test)
- [ ] No `dd()`, `dump()`, or `console.log` left in the code
- [ ] Code matches the relevant rule in `.claude/rules/`
- [ ] [memory/tech-debt.md](../memory/tech-debt.md) updated (mark resolved or log progress)
