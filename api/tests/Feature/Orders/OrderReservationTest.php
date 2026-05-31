<?php

namespace Tests\Feature\Orders;

use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Event;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Organizer;
use App\Models\TicketType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderReservationTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_reservation_reduces_available_quantity(): void
    {
        $organizer = Organizer::factory()->create();
        $event = Event::factory()->for($organizer)->create();
        $ticketType = TicketType::factory()->for($event)->withQuota(5)->create();
        $order = Order::factory()->for($event)->for($organizer)
            ->state(['status' => OrderStatus::PENDING, 'reserved_until' => now()->addMinutes(15)])
            ->create();

        for ($i = 0; $i < 3; $i++) {
            OrderItem::factory()->for($order)->state(['ticket_type_id' => $ticketType->id])->create();
        }

        $this->assertSame(2, $ticketType->fresh()->getAvailableQuantity());
    }

    public function test_expired_reservation_does_not_reduce_available_quantity(): void
    {
        $organizer = Organizer::factory()->create();
        $event = Event::factory()->for($organizer)->create();
        $ticketType = TicketType::factory()->for($event)->withQuota(5)->create();
        $order = Order::factory()->for($event)->for($organizer)
            ->state(['status' => OrderStatus::PENDING, 'reserved_until' => now()->subMinute()])
            ->create();

        for ($i = 0; $i < 3; $i++) {
            OrderItem::factory()->for($order)->state(['ticket_type_id' => $ticketType->id])->create();
        }

        $this->assertSame(5, $ticketType->fresh()->getAvailableQuantity());
    }

    public function test_cancel_expired_orders_command_releases_reservations(): void
    {
        $organizer = Organizer::factory()->create();
        $event = Event::factory()->for($organizer)->create();
        $expired = Order::factory()->for($event)->for($organizer)
            ->state(['status' => OrderStatus::PENDING, 'reserved_until' => now()->subMinute()])
            ->create();
        $active = Order::factory()->for($event)->for($organizer)
            ->state(['status' => OrderStatus::PENDING, 'reserved_until' => now()->addMinutes(10)])
            ->create();
        $processing = Order::factory()->for($event)->for($organizer)
            ->state(['status' => OrderStatus::PROCESSING, 'reserved_until' => now()->subMinute()])
            ->create();

        $this->artisan('orders:cancel-expired')->assertSuccessful();

        $this->assertSame(OrderStatus::CANCELLED, $expired->fresh()->status);
        $this->assertSame(OrderStatus::PENDING, $active->fresh()->status);
        // PROCESSING não é cancelado pelo command — o job está trabalhando nele.
        $this->assertSame(OrderStatus::PROCESSING, $processing->fresh()->status);
    }

    public function test_store_order_sets_reserved_until_15_minutes_ahead(): void
    {
        $organizer = Organizer::factory()->create();
        $event = Event::factory()->for($organizer)->create();
        Category::factory()->for($event)->create();
        $ticketType = TicketType::factory()->for($event)->create();

        $payload = [
            'event_id' => $event->id,
            'items' => [[
                'ticket_type_id' => $ticketType->id,
                'participant_data' => [
                    'name' => 'Comprador Teste',
                    'email' => 'comprador@test.com',
                    'cpf' => '52998224725',
                    'phone' => '11999990000',
                    'birthdate' => '1990-01-01',
                ],
            ]],
        ];

        $response = $this->postJson('/api/orders', $payload);
        $response->assertCreated();

        $order = Order::where('reference', $response->json('order.reference'))->first();
        $this->assertNotNull($order->reserved_until);
        $this->assertTrue(
            $order->reserved_until->between(now()->addMinutes(14), now()->addMinutes(16))
        );
    }
}
