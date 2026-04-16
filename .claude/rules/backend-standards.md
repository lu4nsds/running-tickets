# rules/backend-standards.md — Laravel Backend Standards

> Target: Laravel 12 + PHP 8.2 official best practices.
> The current codebase is the source of **business truth**, not technical standards.
> Back to manifesto: [../CLAUDE.md](../CLAUDE.md)

---

## Folder Structure (Target)

```
api/app/
├── Enums/                 # PHP 8.1+ backed enums — all status fields
├── Exceptions/            # Custom exception handlers
├── Http/
│   ├── Controllers/Api/   # Thin — HTTP only, no business logic
│   ├── Middleware/        # Route-group authorization
│   ├── Requests/          # Form Requests (validation + route auth)
│   └── Resources/         # API Resource classes (response shaping)
├── Jobs/                  # Queued async work
├── Mail/                  # Mailable classes
├── Models/                # Eloquent models
├── Observers/             # Model lifecycle side effects
├── Policies/              # Per-model authorization (one per model)
└── Services/              # Business logic (one class per concern)
```

---

## Naming Conventions

| Item | Convention | Example |
|------|-----------|---------|
| Model | PascalCase singular | `OrderItem`, `TicketType` |
| Controller | Model + `Controller` | `OrderController` |
| Form Request | Action + Model + `Request` | `StoreOrderRequest`, `UpdateEventRequest` |
| Policy | Model + `Policy` | `OrderPolicy`, `TicketPolicy` |
| Service | Concern + `Service` | `OrderService`, `MercadoPagoService` |
| Job | Verb + Noun + `Job` | `GenerateOrderTicketsJob` |
| Observer | Model + `Observer` | `OrderObserver` |
| Resource | Model + `Resource` | `OrderResource`, `EventResource` |
| Enum | PascalCase singular | `OrderStatus`, `TicketStatus` |
| Migration | `create_{table}_table` | `2026_01_10_create_orders_table` |
| Route | kebab-case | `/ticket-types`, `/payment-settings` |

---

## Controllers — Target Standard

Controllers must be **thin**. They only:
1. Receive the HTTP request (via Form Request)
2. Delegate to a Service or Model
3. Return an HTTP response (via API Resource)

```php
// app/Http/Controllers/Api/OrderController.php

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orderService) {}

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $order = $this->orderService->create(
            eventId:    $request->validated('event_id'),
            items:      $request->validated('items'),
            buyerEmail: $request->validated('buyer_email'),
            userId:     $request->user()?->id,
        );

        return new JsonResponse(new OrderResource($order), 201);
    }

    public function show(Order $order): JsonResponse
    {
        $this->authorize('view', $order);
        return new JsonResponse(new OrderResource($order->load('items.ticket', 'event')));
    }

    public function cancel(Order $order): JsonResponse
    {
        $this->authorize('cancel', $order);
        $this->orderService->cancel($order);
        return new JsonResponse(new OrderResource($order->refresh()));
    }
}
```

**Never in a controller:**
- Business logic or conditional branching on business rules
- Inline authorization (`if ($user->role !== ...)`) — use `$this->authorize()`
- Raw DB queries — use Eloquent + scopes
- Email sending or file generation — use Jobs
- `$request->input()` — always use `$request->validated()`

---

## Form Requests — Target Standard

Every write endpoint must use a Form Request. `authorize()` = route-level gate. `rules()` = field validation.

```php
// app/Http/Requests/StoreOrderRequest.php

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Orders are public (OptionalAuth middleware handles auth)
    }

    public function rules(): array
    {
        return [
            'event_id'                  => ['required', 'integer', 'exists:events,id'],
            'buyer_email'               => ['required', 'email', 'max:255'],
            'items'                     => ['required', 'array', 'min:1'],
            'items.*.ticket_type_id'    => ['required', 'integer', 'exists:ticket_types,id'],
            'items.*.category_id'       => ['nullable', 'integer', 'exists:categories,id'],
            'items.*.participant'       => ['required', 'array'],
            'items.*.participant.name'  => ['required', 'string', 'max:255'],
            'items.*.participant.email' => ['required', 'email', 'max:255'],
            'items.*.participant.cpf'   => ['required', 'string', 'size:11'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.*.participant.cpf.size' => 'CPF must be exactly 11 digits.',
        ];
    }
}
```

**Rules:** Use `validated()` in controllers · `after()` for cross-field validation · custom `Rule` objects for CPF/CNPJ · always define `messages()`

---

## Policies — Target Standard

One Policy per model. Register in `AuthServiceProvider::$policies`.

```php
// app/Policies/OrderPolicy.php

class OrderPolicy
{
    public function viewAny(?User $user): bool
    {
        return $user !== null;
    }

    public function view(User $user, Order $order): bool
    {
        return $user->isSuperAdmin()
            || $order->user_id === $user->id
            || $user->canAccessOrganizer($order->organizer_id);
    }

    public function cancel(User $user, Order $order): bool
    {
        return $this->view($user, $order) && $order->canCancel();
    }
}
```

**Controller usage:**
```php
$this->authorize('view', $order);     // throws 403 automatically
$this->authorize('cancel', $order);
```

**Current State → Target:**

| Model | Policy exists? | Action |
|-------|---------------|--------|
| Order | Partial/inline | Extract to `OrderPolicy` — see [TD-001](../memory/tech-debt.md) |
| Ticket | Inline | Create `TicketPolicy` |
| Event | Inline | Create `EventPolicy` |
| Organizer | Inline | Create `OrganizerPolicy` |
| TicketType | Missing | Create `TicketTypePolicy` |
| Category | Missing | Create `CategoryPolicy` |

---

## Services — Target Standard

Services contain business logic. Plain PHP classes, injected via constructor.

```php
// app/Services/TicketService.php

class TicketService
{
    public function generate(OrderItem $item): Ticket
    {
        $code   = (string) Str::uuid();
        $qrPath = "tickets/{$code}.svg";

        Storage::put($qrPath, QrCode::format('svg')->size(300)->errorCorrection('H')->generate($code));

        return Ticket::create([
            'order_item_id' => $item->id,
            'code'          => $code,
            'qr_path'       => $qrPath,
            'status'        => TicketStatus::Active,
            'issued_at'     => now(),
        ]);
    }
}
```

**Create a Service when:**
- Logic spans multiple models
- Integrating with a third-party API (Mercado Pago, S3)
- Logic is reused across multiple controllers
- Complex orchestration (order creation, ticket generation)

**Don't create a Service for:**
- Simple single-model CRUD (call Model directly from controller)
- Logic that belongs in the model itself (`canCancel()`, `isPaid()`)

**Target Services for this project:**
- `OrderService` — create, calculate total, cancel
- `TicketService` — generate ticket + QR
- `MercadoPagoService` — payment preference, webhook validation, credential check

---

## Eloquent Models — Target Standard

```php
// app/Models/Order.php

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id', 'organizer_id', 'user_id', 'reference',
        'total_cents', 'currency', 'status', 'buyer_email',
        'payment_gateway', 'payment_id', 'metadata',
    ];

    protected $casts = [
        'status'      => OrderStatus::class,   // ← Enum cast
        'metadata'    => 'array',
        'total_cents' => 'integer',
    ];

    // ── Relationships ──────────────────────────────────────────────
    public function event(): BelongsTo     { return $this->belongsTo(Event::class); }
    public function organizer(): BelongsTo { return $this->belongsTo(Organizer::class); }
    public function user(): BelongsTo      { return $this->belongsTo(User::class); }
    public function items(): HasMany       { return $this->hasMany(OrderItem::class); }

    // ── Query Scopes ────────────────────────────────────────────────
    public function scopePaid(Builder $query): void
    {
        $query->where('status', OrderStatus::Paid);
    }

    public function scopeForOrganizer(Builder $query, int $organizerId): void
    {
        $query->where('organizer_id', $organizerId);
    }

    // ── Business Methods ────────────────────────────────────────────
    public function canCancel(): bool { return $this->status === OrderStatus::Pending; }
    public function isPaid(): bool    { return $this->status === OrderStatus::Paid; }

    // ── Route Model Binding ─────────────────────────────────────────
    public function getRouteKeyName(): string { return 'reference'; }
}
```

**Rules:**
- `$fillable` always (never `$guarded = []`)
- Cast Enums: `'status' => OrderStatus::class`
- Cast JSON: `'metadata' => 'array'`
- All relationships with explicit return types
- Business rules as model methods (`canX()`, `isX()`)
- Repeated query conditions as named scopes (`scopeX()`)

---

## Enums — Target Standard

```php
// app/Enums/OrderStatus.php

enum OrderStatus: string
{
    case Pending   = 'pending';
    case Paid      = 'paid';
    case Cancelled = 'cancelled';
    case Refunded  = 'refunded';

    public function label(): string
    {
        return match($this) {
            self::Pending   => 'Pending',
            self::Paid      => 'Paid',
            self::Cancelled => 'Cancelled',
            self::Refunded  => 'Refunded',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Pending   => 'yellow',
            self::Paid      => 'green',
            self::Cancelled => 'gray',
            self::Refunded  => 'red',
        };
    }
}
```

**Rules:**
- All status fields = backed string Enums in `app/Enums/`
- Cast in model: `'status' => OrderStatus::class`
- Compare with Enum case, never raw string: `$order->status === OrderStatus::Paid` ✓
- Note: `EventStatus` values `'ativo'/'inativo'` are Portuguese — tracked as [TD-011](../memory/tech-debt.md)

---

## API Resources — Target Standard

```php
// app/Http/Resources/OrderResource.php

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'reference'    => $this->reference,
            'status'       => $this->status->value,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->color(),
            'total_cents'  => $this->total_cents,
            'currency'     => $this->currency,
            'buyer_email'  => $this->buyer_email,
            'items'        => OrderItemResource::collection($this->whenLoaded('items')),
            'event'        => new EventResource($this->whenLoaded('event')),
            'created_at'   => $this->created_at->toIso8601String(),
        ];
    }
}
```

**Rules:**
- `$this->whenLoaded()` for ALL relationships — prevents N+1
- Dates as ISO 8601 strings
- Expose Enum `->value` + `->label()` (frontend needs both)
- Never expose `payment_id`, raw gateway `metadata` to public consumers

---

## Jobs — Target Standard

```php
// app/Jobs/GenerateOrderTicketsJob.php

class GenerateOrderTicketsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 120;

    public function __construct(private readonly Order $order) {}

    public function handle(TicketService $ticketService): void
    {
        $this->order->items->each(function (OrderItem $item) use ($ticketService) {
            $ticket = $ticketService->generate($item);
            Log::info('Ticket generated', ['code' => $ticket->code]);
        });

        Mail::to($this->order->buyer_email)->send(new OrderPaidMail($this->order));
    }

    public function failed(Throwable $e): void
    {
        Log::error('GenerateOrderTicketsJob failed', [
            'order_id' => $this->order->id,
            'error'    => $e->getMessage(),
        ]);
    }
}
```

**Rules:**
- Always implement `ShouldQueue`
- Always define `$tries` and `$timeout`
- Always implement `failed()` for error logging
- Inject services via `handle()` parameters
- `SerializesModels` re-fetches the model fresh from DB on execution

---

## Observers — Target Standard

```php
// app/Observers/OrderObserver.php

class OrderObserver
{
    public function updated(Order $order): void
    {
        if ($order->wasChanged('status') && $order->status === OrderStatus::Paid) {
            Cache::tags('dashboard')->flush();
            GenerateOrderTicketsJob::dispatch($order);
        }
    }
}
```

**Register in** `AppServiceProvider::boot()`:
```php
Order::observe(OrderObserver::class);
```

**Rules:** Use `wasChanged()` to check specific field change · Observers for side effects only · Delegate work to Services/Jobs

---

## Testing — Target Standard (Pest)

```
api/tests/
├── Feature/
│   ├── Auth/LoginTest.php · RegisterTest.php
│   ├── Events/EventListTest.php
│   ├── Orders/CreateOrderTest.php · CancelOrderTest.php
│   └── Tickets/ValidateTicketTest.php
└── Unit/
    ├── Models/OrderTest.php
    └── Services/TicketServiceTest.php
```

```php
it('creates an order for an active event', function () {
    $event      = Event::factory()->active()->create();
    $ticketType = TicketType::factory()->for($event)->create();

    $this->postJson('/api/orders', [
        'event_id'    => $event->id,
        'buyer_email' => 'buyer@test.com',
        'items'       => [[
            'ticket_type_id' => $ticketType->id,
            'participant'    => ['name' => 'John Doe', 'email' => 'j@test.com', 'cpf' => '12345678901'],
        ]],
    ])
    ->assertStatus(201)
    ->assertJsonPath('data.status', 'pending');
});
```

**Rules:**
- Pest only (no PHPUnit class syntax)
- Use model factories for test data
- Feature tests hit the real DB (`RefreshDatabase`)
- Never mock the database
- Always test: happy path + at least one rejection path per endpoint

---

## Current State → Target Migration Path

| Area | Current State | Migration Path |
|------|--------------|----------------|
| Authorization | Inline `if` checks in controllers | Create `app/Policies/`, move logic, use `authorize()` — see [TD-001](../memory/tech-debt.md) |
| Service Layer | Logic scattered in controllers/jobs | Extract to `OrderService`, `TicketService` — see [TD-003](../memory/tech-debt.md) |
| Testing | Sparse/absent | Add Pest Feature tests per endpoint before refactoring — see [TD-002](../memory/tech-debt.md) |
| Enums | Partially cast | Complete `$casts` on all models, eliminate raw string comparisons — see [TD-006](../memory/tech-debt.md) |
| Resources | Present but incomplete | Audit for raw `response()->json()` returns and wrap in Resources |
| Form Requests | Present | Audit all write methods for missing Form Requests |
