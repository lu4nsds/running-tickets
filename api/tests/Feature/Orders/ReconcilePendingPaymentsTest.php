<?php

namespace Tests\Feature\Orders;

use App\Enums\OrderStatus;
use App\Jobs\GenerateOrderTicketsJob;
use App\Models\Category;
use App\Models\Event;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Organizer;
use App\Models\TicketType;
use App\Services\MercadoPagoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ReconcilePendingPaymentsTest extends TestCase
{
    use RefreshDatabase;

    private function makeProcessingOrder(array $orderState = []): Order
    {
        $organizer = Organizer::factory()->create();
        $event = Event::factory()->for($organizer)->create();
        $category = Category::factory()->for($event)->create();
        $ticketType = TicketType::factory()->for($event)->create();

        $order = Order::factory()
            ->for($event)
            ->for($organizer)
            ->state(array_merge([
                'status' => OrderStatus::PROCESSING,
                'total_cents' => $ticketType->price_cents,
                'payment_id' => '1347336257',
            ], $orderState))
            ->create();

        OrderItem::factory()
            ->for($order)
            ->state([
                'ticket_type_id' => $ticketType->id,
                'category_id' => $category->id,
            ])
            ->create();

        return $order->fresh()->load('items');
    }

    public function test_approved_payment_marks_order_paid_and_dispatches_tickets_job(): void
    {
        Mail::fake();
        Bus::fake(); // evita rodar GenerateOrderTicketsJob (WhatsApp/PDF) inline

        $order = $this->makeProcessingOrder();

        $this->mock(MercadoPagoService::class, function ($mock) {
            $mock->shouldReceive('getPaymentById')->andReturn([
                'id' => 1347336257,
                'status' => 'approved',
                'status_detail' => 'accredited',
                'transaction_amount' => 50.00,
                'transaction_details' => ['net_received_amount' => 48.5, 'total_paid_amount' => 50.0],
                'payment_method_id' => 'visa',
                'payment_type_id' => 'credit_card',
                'installments' => 1,
            ]);
        });

        $this->artisan('payments:reconcile-pending')->assertSuccessful();

        $order->refresh();
        $this->assertSame(OrderStatus::PAID, $order->status);
        $this->assertSame('approved', $order->payment_response_body['outcome']);

        Bus::assertDispatched(GenerateOrderTicketsJob::class, fn ($job) => $job->order->id === $order->id);
    }

    public function test_rejected_payment_marks_order_failed(): void
    {
        Mail::fake();

        $order = $this->makeProcessingOrder();

        $this->mock(MercadoPagoService::class, function ($mock) {
            $mock->shouldReceive('getPaymentById')->andReturn([
                'id' => 1347336257,
                'status' => 'rejected',
                'status_detail' => 'cc_rejected_insufficient_amount',
                'transaction_amount' => 50.00,
                'transaction_details' => [],
                'payment_method_id' => 'visa',
                'payment_type_id' => 'credit_card',
                'installments' => 1,
            ]);
        });

        $this->artisan('payments:reconcile-pending')->assertSuccessful();

        $order->refresh();
        $this->assertSame(OrderStatus::FAILED, $order->status);
        $this->assertSame('card_declined', $order->payment_response_body['failure_reason']);
    }

    public function test_in_process_payment_keeps_order_processing(): void
    {
        $order = $this->makeProcessingOrder();

        $this->mock(MercadoPagoService::class, function ($mock) {
            $mock->shouldReceive('getPaymentById')->andReturn([
                'id' => 1347336257,
                'status' => 'in_process',
                'status_detail' => 'pending_contingency',
                'transaction_amount' => 50.00,
                'transaction_details' => [],
                'payment_method_id' => 'visa',
                'payment_type_id' => 'credit_card',
                'installments' => 1,
            ]);
        });

        $this->artisan('payments:reconcile-pending')->assertSuccessful();

        $this->assertSame(OrderStatus::PROCESSING, $order->fresh()->status);
    }

    public function test_orders_outside_window_are_not_reconciled(): void
    {
        $order = $this->makeProcessingOrder(['updated_at' => now()->subHour()]);

        $mock = $this->mock(MercadoPagoService::class);
        $mock->shouldNotReceive('getPaymentById');

        $this->artisan('payments:reconcile-pending --minutes=30')->assertSuccessful();

        $this->assertSame(OrderStatus::PROCESSING, $order->fresh()->status);
    }
}
