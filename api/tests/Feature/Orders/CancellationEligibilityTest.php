<?php

namespace Tests\Feature\Orders;

use App\Enums\TicketStatus;
use App\Models\Event;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Organizer;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CancellationEligibilityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Cria um pedido pago do $owner com um ingresso, permitindo customizar
     * paid_at, o status do ingresso e os atributos do evento.
     *
     * @param  array<string, mixed>  $eventState
     */
    private function paidOrder(User $owner, ?\DateTimeInterface $paidAt = null, TicketStatus $ticketStatus = TicketStatus::ACTIVE, array $eventState = []): Order
    {
        $organizer = Organizer::factory()->create();
        $event = Event::factory()->for($organizer)->state($eventState)->create();
        $ticketType = TicketType::factory()->for($event)->create();

        $order = Order::factory()->for($event)->for($organizer)->paid()
            ->state([
                'user_id' => $owner->id,
                'payment_id' => '999',
                'paid_at' => $paidAt ?? now(),
            ])
            ->create();

        $item = OrderItem::factory()->for($order)
            ->state(['ticket_type_id' => $ticketType->id])
            ->create();
        Ticket::factory()->for($item, 'orderItem')->state(['status' => $ticketStatus])->create();

        return $order->fresh();
    }

    public function test_can_request_cancellation_flag_is_true_within_window(): void
    {
        $owner = User::factory()->create();
        $event = $this->paidOrder($owner, now()->subDays(2))->event_id;

        $this->actingAs($owner, 'client')
            ->getJson("/api/tickets?event_id={$event}")
            ->assertStatus(200)
            ->assertJsonPath('data.0.order.can_request_cancellation', true);
    }

    public function test_cannot_request_cancellation_after_seven_days(): void
    {
        $owner = User::factory()->create();
        $order = $this->paidOrder($owner, now()->subDays(8));

        $this->assertFalse($order->canRequestCancellation());

        $this->actingAs($owner, 'client')
            ->getJson("/api/tickets?event_id={$order->event_id}")
            ->assertStatus(200)
            ->assertJsonPath('data.0.order.can_request_cancellation', false);

        $this->actingAs($owner, 'client')
            ->postJson('/api/orders/cancellations', [
                'references' => [$order->reference],
                'reason' => 'Passou do prazo.',
            ])
            ->assertStatus(403);
    }

    public function test_cannot_request_cancellation_without_active_tickets(): void
    {
        $owner = User::factory()->create();
        $order = $this->paidOrder($owner, now()->subDay(), TicketStatus::USED);

        $this->assertFalse($order->canRequestCancellation());

        $this->actingAs($owner, 'client')
            ->postJson('/api/orders/cancellations', [
                'references' => [$order->reference],
                'reason' => 'Ingresso já utilizado.',
            ])
            ->assertStatus(403);
    }

    public function test_paid_order_missing_paid_at_is_not_eligible(): void
    {
        $owner = User::factory()->create();
        $order = $this->paidOrder($owner);
        $order->update(['paid_at' => null]);

        $this->assertFalse($order->fresh()->canRequestCancellation());
    }

    public function test_event_flag_allows_cancellation_after_seven_days(): void
    {
        $owner = User::factory()->create();
        $order = $this->paidOrder($owner, now()->subDays(30), TicketStatus::ACTIVE, [
            'allows_late_refund_request' => true,
        ]);

        $this->assertTrue($order->canRequestCancellation());

        $this->actingAs($owner, 'client')
            ->getJson("/api/tickets?event_id={$order->event_id}")
            ->assertStatus(200)
            ->assertJsonPath('data.0.order.can_request_cancellation', true);

        $this->actingAs($owner, 'client')
            ->postJson('/api/orders/cancellations', [
                'references' => [$order->reference],
                'reason' => 'Evento permite solicitação fora do prazo.',
            ])
            ->assertStatus(201);
    }

    public function test_event_flag_does_not_allow_cancellation_after_event_started(): void
    {
        $owner = User::factory()->create();
        $order = $this->paidOrder($owner, now()->subDays(30), TicketStatus::ACTIVE, [
            'allows_late_refund_request' => true,
            'date_start' => now()->subDay(),
            'date_end' => now(),
        ]);

        $this->assertFalse($order->canRequestCancellation());

        $this->actingAs($owner, 'client')
            ->postJson('/api/orders/cancellations', [
                'references' => [$order->reference],
                'reason' => 'Evento já começou.',
            ])
            ->assertStatus(403);
    }

    /**
     * Pedidos pagos antes da criação da coluna `paid_at` (migration de
     * 2026-06-23, sem backfill) têm o campo nulo. No ramo da flag o campo não
     * é usado, então eles seguem elegíveis.
     */
    public function test_event_flag_allows_cancellation_without_paid_at(): void
    {
        $owner = User::factory()->create();
        $order = $this->paidOrder($owner, null, TicketStatus::ACTIVE, [
            'allows_late_refund_request' => true,
        ]);
        $order->update(['paid_at' => null]);

        $this->assertTrue($order->fresh()->canRequestCancellation());

        $this->actingAs($owner, 'client')
            ->postJson('/api/orders/cancellations', [
                'references' => [$order->reference],
                'reason' => 'Pedido legado sem paid_at.',
            ])
            ->assertStatus(201);
    }

    public function test_event_flag_does_not_bypass_active_ticket_requirement(): void
    {
        $owner = User::factory()->create();
        $order = $this->paidOrder($owner, now()->subDays(30), TicketStatus::USED, [
            'allows_late_refund_request' => true,
        ]);

        $this->assertFalse($order->canRequestCancellation());

        $this->actingAs($owner, 'client')
            ->postJson('/api/orders/cancellations', [
                'references' => [$order->reference],
                'reason' => 'Ingresso já utilizado.',
            ])
            ->assertStatus(403);
    }
}
