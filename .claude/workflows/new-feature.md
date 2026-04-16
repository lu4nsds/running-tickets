# workflows/new-feature.md — New Feature Implementation Guide

> Follow this guide for any new feature, end-to-end.
> Back to manifesto: [../CLAUDE.md](../CLAUDE.md)

---

## Before You Start

1. **[rules/business-domains.md](../rules/business-domains.md)** — identify which domain the feature belongs to
2. **[rules/architecture.md](../rules/architecture.md)** — understand where it fits in the system
3. **[memory/tech-debt.md](../memory/tech-debt.md)** — don't build on unstable ground
4. **[memory/known-decisions.md](../memory/known-decisions.md)** — don't re-debate settled decisions

---

## Decision Tree

```
Feature needs new data stored?
  YES → Migration + Model
  NO  → Skip to Controller

Business logic spans multiple models OR calls third-party APIs?
  YES → Create a Service
  NO  → Controller can call Model directly (simple CRUD)

Feature does expensive work (email, file gen, external call) after a user action?
  YES → Create a Job (dispatched from Service or Observer)
  NO  → Sync is fine

Side effect triggered by a model state change (e.g. status → paid)?
  YES → Create or extend an Observer
  NO  → Trigger from Controller / Service

Feature acts on a specific resource a user might not own?
  ALWAYS → Create or extend a Policy
```

---

## Step-by-Step Checklist

### 1. Database Migration

```bash
php artisan make:migration create_{table}_table
# or
php artisan make:migration add_{column}_to_{table}_table
```

Follow [rules/database-standards.md](../rules/database-standards.md):
- `->constrained()->cascadeOnDelete()` on all foreign keys
- Index every `WHERE` / `ORDER BY` column
- Money in cents (`price_cents INTEGER`)
- Always implement `down()`

```bash
php artisan migrate
```

---

### 2. Model

```bash
php artisan make:model {ModelName} --factory
```

```php
class Registration extends Model
{
    use HasFactory;

    protected $fillable = ['event_id', 'user_id', 'status', 'data'];

    protected $casts = [
        'status' => RegistrationStatus::class,
        'data'   => 'array',
    ];

    public function event(): BelongsTo { return $this->belongsTo(Event::class); }

    public function scopeConfirmed(Builder $query): void
    {
        $query->where('status', RegistrationStatus::Confirmed);
    }

    public function canCancel(): bool { return $this->status === RegistrationStatus::Pending; }
}
```

- `$fillable` always (never `$guarded = []`)
- Cast Enums and JSON columns in `$casts`
- All relationships with explicit return types
- Business rules as model methods, repeated filters as scopes

---

### 3. Enum (if new status field)

```php
// app/Enums/RegistrationStatus.php

enum RegistrationStatus: string
{
    case Pending   = 'pending';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';

    public function label(): string { return match($this) { ... }; }
    public function color(): string { return match($this) { ... }; }
}
```

---

### 4. Form Request(s)

```bash
php artisan make:request Store{ModelName}Request
php artisan make:request Update{ModelName}Request
```

- `authorize()` = route-level gate (is this type of user allowed at all?)
- `rules()` = field validation
- Always use `$request->validated()` in the controller — never `$request->input()`

---

### 5. Policy

```bash
php artisan make:policy {ModelName}Policy --model={ModelName}
```

Register in `app/Providers/AuthServiceProvider.php`:
```php
protected $policies = [
    Registration::class => RegistrationPolicy::class,
];
```

Implement: `viewAny`, `view`, `create`, `update`, `delete` as needed:
```php
public function view(User $user, Registration $registration): bool
{
    return $user->isSuperAdmin() || $registration->user_id === $user->id;
}
```

---

### 6. Service (if non-trivial business logic)

```php
// app/Services/RegistrationService.php

class RegistrationService
{
    public function register(User $user, Event $event, array $data): Registration
    {
        // validate quota, check category eligibility, create order, etc.
    }
}
```

Inject via constructor in the controller. Let Laravel's service container resolve it automatically.

---

### 7. Job (if async work needed)

```bash
php artisan make:job {VerbNoun}Job
```

```php
class SendRegistrationConfirmationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;

    public function __construct(private readonly Registration $registration) {}

    public function handle(NotificationService $notifications): void { ... }

    public function failed(Throwable $e): void { Log::error(...); }
}
```

---

### 8. Observer (if triggered by model state change)

```bash
php artisan make:observer {ModelName}Observer --model={ModelName}
```

Register in `AppServiceProvider::boot()`:
```php
Registration::observe(RegistrationObserver::class);
```

```php
public function updated(Registration $registration): void
{
    if ($registration->wasChanged('status') && $registration->status === RegistrationStatus::Confirmed) {
        SendRegistrationConfirmationJob::dispatch($registration);
    }
}
```

---

### 9. API Resource

```bash
php artisan make:resource {ModelName}Resource
```

```php
public function toArray(Request $request): array
{
    return [
        'id'           => $this->id,
        'status'       => $this->status->value,
        'status_label' => $this->status->label(),
        'event'        => new EventResource($this->whenLoaded('event')),
        'created_at'   => $this->created_at->toIso8601String(),
    ];
}
```

Always use `$this->whenLoaded()` for relationships.

---

### 10. Controller

```bash
php artisan make:controller Api/{ModelName}Controller --api
```

```php
class RegistrationController extends Controller
{
    public function __construct(private readonly RegistrationService $service) {}

    public function store(StoreRegistrationRequest $request): JsonResponse
    {
        $this->authorize('create', Registration::class);

        $registration = $this->service->register(
            user:  $request->user(),
            event: Event::findOrFail($request->validated('event_id')),
            data:  $request->validated(),
        );

        return new JsonResponse(new RegistrationResource($registration), 201);
    }
}
```

Each method: `authorize()` first, delegate to service, return Resource. Under 20 lines.

---

### 11. Routes

```php
// routes/api.php — place in the correct middleware group

// Public
Route::get('/registrations/{registration}', [RegistrationController::class, 'show']);

// Authenticated
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/registrations', [RegistrationController::class, 'store']);
    Route::delete('/registrations/{registration}', [RegistrationController::class, 'destroy']);
});

// Super Admin
Route::middleware(['auth:sanctum', 'super_admin'])->prefix('admin')->group(function () {
    Route::apiResource('registrations', Admin\RegistrationController::class);
});
```

---

### 12. Frontend — API Module

```js
// client/src/api/registrations.js

import api from './axios'

export const registrationsApi = {
  create: (data)  => api.post('/registrations', data).then(r => r.data),
  list:   ()      => api.get('/registrations').then(r => r.data),
  show:   (id)    => api.get(`/registrations/${id}`).then(r => r.data),
  cancel: (id)    => api.delete(`/registrations/${id}`).then(r => r.data),
}
```

---

### 13. Frontend — Pinia Store (if shared state)

```js
// stores/registrations.js

export const useRegistrationStore = defineStore('registrations', () => {
  const items     = ref([])
  const isLoading = ref(false)

  async function load() {
    isLoading.value = true
    try { items.value = await registrationsApi.list() }
    finally { isLoading.value = false }
  }

  return { items, isLoading, load }
})
```

Create a store when state is needed across multiple components. Otherwise, keep state local or in a composable.

---

### 14. Frontend — Composable (if reusable component logic)

```js
// composables/useRegistrationForm.js

export function useRegistrationForm(eventId) {
  const form        = reactive({ name: '', email: '', cpf: '' })
  const errors      = ref({})
  const isSubmitting = ref(false)

  async function submit() {
    isSubmitting.value = true
    try {
      await registrationsApi.create({ event_id: eventId, ...form })
    } catch (err) {
      errors.value = err.response?.data?.errors ?? {}
    } finally {
      isSubmitting.value = false
    }
  }

  return { form, errors, isSubmitting, submit }
}
```

---

### 15. Frontend — View + Route

```js
// router/index.js — add the route
{
  path: '/registrations',
  component: () => import('@/views/RegistrationsView.vue'),
  meta: { requiresAuth: true },
}
```

- `src/views/{Domain}/RegistrationsView.vue`
- Keep views < 150 lines — extract sub-components if needed
- See [rules/frontend-standards.md § Component Structure Rules](../rules/frontend-standards.md)

---

### 16. Tests

Write tests **before marking the feature done**.

```bash
# Feature test
touch api/tests/Feature/Registrations/CreateRegistrationTest.php
```

Cover at minimum:
- Happy path (correct status code, data persisted)
- Validation failure (422 with field errors)
- Auth failure (401 for protected endpoint)
- Authorization failure (403 for wrong user/role)
- Business rule rejection (e.g. sold out, inactive event)

```php
it('creates a registration for an active event', function () {
    $user  = User::factory()->create();
    $event = Event::factory()->active()->create();

    $this->actingAs($user)->postJson('/api/registrations', [...])
         ->assertStatus(201);
});

it('rejects registration when ticket type is sold out', function () {
    $ticketType = TicketType::factory()->soldOut()->create();

    $this->postJson('/api/registrations', [...])
         ->assertStatus(422);
});
```

---

## Full Checklist (copy-paste)

```
[ ] 1.  Migration created and run
[ ] 2.  Model: $fillable, $casts, relationships, scopes, business methods
[ ] 3.  Enum (if new status field)
[ ] 4.  Form Request(s)
[ ] 5.  Policy registered in AuthServiceProvider
[ ] 6.  Service (if non-trivial logic)
[ ] 7.  Job (if async work)
[ ] 8.  Observer (if triggered by model event)
[ ] 9.  API Resource (with whenLoaded)
[ ] 10. Controller (thin — authorize, delegate, return)
[ ] 11. Routes in correct middleware group
[ ] 12. Frontend API module function
[ ] 13. Pinia store (if shared state)
[ ] 14. Composable (if reusable logic)
[ ] 15. View + route registered
[ ] 16. Tests: happy path + at least one rejection path
```
