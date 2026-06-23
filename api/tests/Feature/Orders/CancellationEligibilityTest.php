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
     * paid_at e o status do ingresso.
     */
    private function paidOrder(User $owner, ?\DateTimeInterface $paidAt = null, TicketStatus $ticketStatus = TicketStatus::ACTIVE): Order
    {
        $organizer = Organizer::factory()->create();
        $event = Event::factory()->for($organizer)->create();
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

        $this->actingAs($owner, 'sanctum')
            ->getJson("/api/tickets?event_id={$event}")
            ->assertStatus(200)
            ->assertJsonPath('data.0.order.can_request_cancellation', true);
    }

    public function test_cannot_request_cancellation_after_seven_days(): void
    {
        $owner = User::factory()->create();
        $order = $this->paidOrder($owner, now()->subDays(8));

        $this->assertFalse($order->canRequestCancellation());

        $this->actingAs($owner, 'sanctum')
            ->getJson("/api/tickets?event_id={$order->event_id}")
            ->assertStatus(200)
            ->assertJsonPath('data.0.order.can_request_cancellation', false);

        $this->actingAs($owner, 'sanctum')
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

        $this->actingAs($owner, 'sanctum')
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
}
