<?php

namespace Tests\Feature\Orders;

use App\Enums\OrderCancellationStatus;
use App\Enums\OrderStatus;
use App\Enums\TicketStatus;
use App\Models\Event;
use App\Models\Order;
use App\Models\OrderCancellation;
use App\Models\OrderItem;
use App\Models\Organizer;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use App\Services\MercadoPagoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MercadoPago\Exceptions\MPApiException;
use Mockery;
use Tests\TestCase;

class OrderCancellationTest extends TestCase
{
    use RefreshDatabase;

    private function makePaidOrder(?User $owner = null): Order
    {
        $organizer = Organizer::factory()->create();
        $event = Event::factory()->for($organizer)->create();
        $ticketType = TicketType::factory()->for($event)->create();

        $order = Order::factory()->for($event)->for($organizer)->paid()
            ->state([
                'user_id' => $owner?->id,
                'payment_id' => '1234567890',
            ])
            ->create();

        $item = OrderItem::factory()->for($order)
            ->state(['ticket_type_id' => $ticketType->id])
            ->create();

        Ticket::factory()->for($item, 'orderItem')->create();

        return $order->fresh();
    }

    private function makeSuperAdmin(): User
    {
        $user = User::factory()->create();

        \DB::table('roles')->insertOrIgnore(['name' => 'Super Admin', 'slug' => 'super_admin', 'created_at' => now(), 'updated_at' => now()]);
        $role = \DB::table('roles')->where('slug', 'super_admin')->first();
        \DB::table('user_roles')->insertOrIgnore(['user_id' => $user->id, 'role_id' => $role->id]);

        return $user->fresh();
    }

    // ── storeBatch (comprador) ───────────────────────────────────────────────

    public function test_owner_can_request_cancellation_of_paid_order(): void
    {
        $owner = User::factory()->create();
        $order = $this->makePaidOrder($owner);

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/orders/cancellations', [
                'references' => [$order->reference],
                'reason' => 'Não poderei comparecer ao evento.',
            ])
            ->assertStatus(201)
            ->assertJsonPath('cancellations.0.status', 'pending');

        $this->assertDatabaseHas('order_cancellations', [
            'order_id' => $order->id,
            'requested_by' => $owner->id,
            'status' => 'pending',
        ]);
    }

    public function test_owner_can_request_cancellation_of_multiple_orders_in_one_request(): void
    {
        $owner = User::factory()->create();
        $orderA = $this->makePaidOrder($owner);
        $orderB = $this->makePaidOrder($owner);

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/orders/cancellations', [
                'references' => [$orderA->reference, $orderB->reference],
                'reason' => 'Não poderei comparecer ao evento.',
            ])
            ->assertStatus(201)
            ->assertJsonCount(2, 'cancellations');

        $this->assertDatabaseHas('order_cancellations', [
            'order_id' => $orderA->id,
            'requested_by' => $owner->id,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('order_cancellations', [
            'order_id' => $orderB->id,
            'requested_by' => $owner->id,
            'status' => 'pending',
        ]);
    }

    public function test_reason_is_required(): void
    {
        $owner = User::factory()->create();
        $order = $this->makePaidOrder($owner);

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/orders/cancellations', [
                'references' => [$order->reference],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('reason');
    }

    public function test_references_are_required(): void
    {
        $owner = User::factory()->create();

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/orders/cancellations', [
                'reason' => 'Mudei de ideia.',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('references');
    }

    public function test_cannot_request_cancellation_of_unpaid_order(): void
    {
        $owner = User::factory()->create();
        $organizer = Organizer::factory()->create();
        $event = Event::factory()->for($organizer)->create();

        $order = Order::factory()->for($event)->for($organizer)->pending()
            ->state(['user_id' => $owner->id])
            ->create();

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/orders/cancellations', [
                'references' => [$order->reference],
                'reason' => 'Mudei de ideia.',
            ])
            ->assertStatus(403);
    }

    public function test_cannot_create_duplicate_pending_request(): void
    {
        $owner = User::factory()->create();
        $order = $this->makePaidOrder($owner);

        OrderCancellation::factory()->for($order)->create([
            'requested_by' => $owner->id,
        ]);

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/orders/cancellations', [
                'references' => [$order->reference],
                'reason' => 'Segunda tentativa.',
            ])
            ->assertStatus(403);
    }

    public function test_non_owner_cannot_request_cancellation(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $order = $this->makePaidOrder($owner);

        $this->actingAs($stranger, 'sanctum')
            ->postJson('/api/orders/cancellations', [
                'references' => [$order->reference],
                'reason' => 'Não é meu pedido.',
            ])
            ->assertStatus(403);
    }

    public function test_batch_rolls_back_when_one_order_is_not_owned(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $ownOrder = $this->makePaidOrder($owner);
        $strangerOrder = $this->makePaidOrder($stranger);

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/orders/cancellations', [
                'references' => [$ownOrder->reference, $strangerOrder->reference],
                'reason' => 'Tentativa de cancelar pedido de terceiro.',
            ])
            ->assertStatus(403);

        // Nenhuma solicitação criada (rollback da transação)
        $this->assertDatabaseCount('order_cancellations', 0);
    }

    public function test_requesting_cancellation_deactivates_tickets(): void
    {
        $owner = User::factory()->create();
        $order = $this->makePaidOrder($owner);

        $ticket = Ticket::whereIn('order_item_id', $order->items()->pluck('id'))->first();
        $this->assertSame(TicketStatus::ACTIVE, $ticket->status);

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/orders/cancellations', [
                'references' => [$order->reference],
                'reason' => 'Não poderei comparecer.',
            ])
            ->assertStatus(201);

        $this->assertSame(TicketStatus::INACTIVE, $ticket->fresh()->status);
    }

    public function test_rejecting_cancellation_reactivates_tickets(): void
    {
        $admin = $this->makeSuperAdmin();
        $owner = User::factory()->create();
        $order = $this->makePaidOrder($owner);

        // Comprador solicita: ingressos ficam inativos.
        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/orders/cancellations', [
                'references' => [$order->reference],
                'reason' => 'Não poderei comparecer.',
            ])
            ->assertStatus(201);

        $ticket = Ticket::whereIn('order_item_id', $order->items()->pluck('id'))->first();
        $this->assertSame(TicketStatus::INACTIVE, $ticket->fresh()->status);

        $cancellation = OrderCancellation::where('order_id', $order->id)->firstOrFail();

        // Admin rejeita: ingressos voltam a ficar ativos.
        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/admin/cancellations/{$cancellation->id}/reject", [
                'review_notes' => 'Fora do prazo.',
            ])
            ->assertStatus(200);

        $this->assertSame(TicketStatus::ACTIVE, $ticket->fresh()->status);
    }

    // ── approve (super admin) ──────────────────────────────────────────────────

    public function test_super_admin_can_approve_and_refund(): void
    {
        $this->mock(MercadoPagoService::class, function ($mock) {
            $mock->shouldReceive('refundPayment')
                ->once()
                ->andReturn(['id' => 'refund-999', 'status' => 'approved']);
        });

        $admin = $this->makeSuperAdmin();
        $owner = User::factory()->create();
        $order = $this->makePaidOrder($owner);
        $request = OrderCancellation::factory()->for($order)->create([
            'requested_by' => $owner->id,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/admin/cancellations/{$request->id}/approve")
            ->assertStatus(200)
            ->assertJsonPath('cancellation.status', 'approved');

        $this->assertSame(OrderStatus::REFUNDED, $order->fresh()->status);
        $this->assertDatabaseHas('order_cancellations', [
            'id' => $request->id,
            'status' => 'approved',
            'refund_id' => 'refund-999',
            'reviewed_by' => $admin->id,
        ]);

        $ticket = Ticket::whereIn('order_item_id', $order->items()->pluck('id'))->first();
        $this->assertSame(TicketStatus::REFUNDED, $ticket->fresh()->status);
    }

    public function test_approve_refunds_all_tickets_of_a_multi_ticket_order(): void
    {
        $this->mock(MercadoPagoService::class, function ($mock) {
            $mock->shouldReceive('refundPayment')
                ->once()
                ->andReturn(['id' => 'refund-multi', 'status' => 'approved']);
        });

        $admin = $this->makeSuperAdmin();
        $owner = User::factory()->create();

        $organizer = Organizer::factory()->create();
        $event = Event::factory()->for($organizer)->create();
        $ticketType = TicketType::factory()->for($event)->create();
        $order = Order::factory()->for($event)->for($organizer)->paid()
            ->state(['user_id' => $owner->id, 'payment_id' => '999'])
            ->create();

        // 3 participantes / 3 ingressos no mesmo pedido
        $itemIds = [];
        for ($i = 0; $i < 3; $i++) {
            $item = OrderItem::factory()->for($order)
                ->state(['ticket_type_id' => $ticketType->id])
                ->create();
            Ticket::factory()->for($item, 'orderItem')->create();
            $itemIds[] = $item->id;
        }

        $request = OrderCancellation::factory()->for($order)->create([
            'requested_by' => $owner->id,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/admin/cancellations/{$request->id}/approve")
            ->assertStatus(200);

        // TODOS os ingressos devem estar reembolsados
        $refunded = Ticket::whereIn('order_item_id', $itemIds)
            ->where('status', TicketStatus::REFUNDED->value)
            ->count();
        $this->assertSame(3, $refunded);
    }

    public function test_approve_does_not_change_state_when_gateway_fails(): void
    {
        $this->mock(MercadoPagoService::class, function ($mock) {
            $mock->shouldReceive('refundPayment')
                ->once()
                ->andThrow(Mockery::mock(MPApiException::class)->shouldIgnoreMissing());
        });

        $admin = $this->makeSuperAdmin();
        $owner = User::factory()->create();
        $order = $this->makePaidOrder($owner);
        $request = OrderCancellation::factory()->for($order)->create([
            'requested_by' => $owner->id,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/admin/cancellations/{$request->id}/approve")
            ->assertStatus(422);

        // Pedido segue PAGO e solicitação segue PENDENTE (rollback)
        $this->assertSame(OrderStatus::PAID, $order->fresh()->status);
        $this->assertSame(OrderCancellationStatus::PENDING, $request->fresh()->status);
    }

    public function test_cannot_approve_already_reviewed_request(): void
    {
        $admin = $this->makeSuperAdmin();
        $owner = User::factory()->create();
        $order = $this->makePaidOrder($owner);
        $request = OrderCancellation::factory()->for($order)->rejected()->create([
            'requested_by' => $owner->id,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/admin/cancellations/{$request->id}/approve")
            ->assertStatus(422);
    }

    // ── reject (super admin) ────────────────────────────────────────────────────

    public function test_super_admin_can_reject_without_touching_gateway(): void
    {
        $this->mock(MercadoPagoService::class, function ($mock) {
            $mock->shouldNotReceive('refundPayment');
        });

        $admin = $this->makeSuperAdmin();
        $owner = User::factory()->create();
        $order = $this->makePaidOrder($owner);
        $request = OrderCancellation::factory()->for($order)->create([
            'requested_by' => $owner->id,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/admin/cancellations/{$request->id}/reject", [
                'review_notes' => 'Fora do prazo de cancelamento.',
            ])
            ->assertStatus(200)
            ->assertJsonPath('cancellation.status', 'rejected');

        $this->assertSame(OrderStatus::PAID, $order->fresh()->status);
        $this->assertDatabaseHas('order_cancellations', [
            'id' => $request->id,
            'status' => 'rejected',
            'review_notes' => 'Fora do prazo de cancelamento.',
        ]);
    }

    // ── autorização ──────────────────────────────────────────────────────────────

    public function test_non_admin_cannot_approve(): void
    {
        $owner = User::factory()->create();
        $order = $this->makePaidOrder($owner);
        $request = OrderCancellation::factory()->for($order)->create([
            'requested_by' => $owner->id,
        ]);

        $this->actingAs($owner, 'sanctum')
            ->postJson("/api/admin/cancellations/{$request->id}/approve")
            ->assertStatus(403);
    }

    public function test_super_admin_can_list_event_cancellations(): void
    {
        $admin = $this->makeSuperAdmin();
        $owner = User::factory()->create();
        $order = $this->makePaidOrder($owner);
        OrderCancellation::factory()->for($order)->create([
            'requested_by' => $owner->id,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/admin/events/{$order->event_id}/cancellations")
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }
}
