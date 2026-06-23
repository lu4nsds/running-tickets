<?php

namespace Tests\Feature\Orders;

use App\Enums\OrderStatus;
use App\Models\Event;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Organizer;
use App\Models\Ticket;
use App\Models\TicketType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RepairDowngradedPaidOrdersTest extends TestCase
{
    use RefreshDatabase;

    private function downgradedOrder(): Order
    {
        $organizer = Organizer::factory()->create();
        $event = Event::factory()->for($organizer)->create();
        $ticketType = TicketType::factory()->for($event)->create();

        // Aprovado no gateway (tem ticket gerado + outcome approved) mas com
        // status rebaixado para PENDING pelo bug do webhook.
        $order = Order::factory()->for($event)->for($organizer)->pending()
            ->state([
                'payment_id' => '777',
                'payment_response_body' => ['outcome' => 'approved', 'processed_at' => now()->subDay()->toIso8601String()],
            ])
            ->create();

        $item = OrderItem::factory()->for($order)
            ->state(['ticket_type_id' => $ticketType->id])
            ->create();
        Ticket::factory()->for($item, 'orderItem')->create();

        return $order->fresh();
    }

    public function test_dry_run_does_not_modify_orders(): void
    {
        $order = $this->downgradedOrder();

        $this->artisan('orders:repair-downgraded', ['--dry-run' => true])
            ->assertExitCode(0);

        $this->assertSame(OrderStatus::PENDING, $order->fresh()->status);
    }

    public function test_repair_restores_downgraded_order_to_paid(): void
    {
        $order = $this->downgradedOrder();

        $this->artisan('orders:repair-downgraded')->assertExitCode(0);

        $fresh = $order->fresh();
        $this->assertSame(OrderStatus::PAID, $fresh->status);
        $this->assertNotNull($fresh->paid_at);
    }

    public function test_repair_ignores_legitimately_pending_orders_without_tickets(): void
    {
        $organizer = Organizer::factory()->create();
        $event = Event::factory()->for($organizer)->create();
        $order = Order::factory()->for($event)->for($organizer)->pending()->create();

        $this->artisan('orders:repair-downgraded')->assertExitCode(0);

        $this->assertSame(OrderStatus::PENDING, $order->fresh()->status);
    }
}
