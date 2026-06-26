<?php

namespace Tests\Feature\Orders;

use App\Enums\OrderStatus;
use App\Models\Event;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Organizer;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Services\PaymentResultService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WebhookIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    private function paidOrder(): Order
    {
        $organizer = Organizer::factory()->create();
        $event = Event::factory()->for($organizer)->create();
        $ticketType = TicketType::factory()->for($event)->create();

        $order = Order::factory()->for($event)->for($organizer)->paid()
            ->state([
                'payment_id' => '1234567890',
                'payment_response_body' => ['outcome' => 'approved', 'processed_at' => now()->toIso8601String()],
            ])
            ->create();

        $item = OrderItem::factory()->for($order)
            ->state(['ticket_type_id' => $ticketType->id])
            ->create();
        Ticket::factory()->for($item, 'orderItem')->create();

        return $order->fresh();
    }

    public function test_late_pending_webhook_does_not_downgrade_a_paid_order(): void
    {
        Mail::fake();
        $order = $this->paidOrder();

        app(PaymentResultService::class)->apply($order, ['status' => 'pending', 'id' => '1234567890']);

        $this->assertSame(OrderStatus::PAID, $order->fresh()->status);
    }

    public function test_late_in_process_webhook_does_not_downgrade_a_paid_order(): void
    {
        Mail::fake();
        $order = $this->paidOrder();

        app(PaymentResultService::class)->apply($order, ['status' => 'in_process', 'id' => '1234567890']);

        $this->assertSame(OrderStatus::PAID, $order->fresh()->status);
    }

    public function test_late_rejected_webhook_does_not_downgrade_a_paid_order(): void
    {
        Mail::fake();
        $order = $this->paidOrder();

        app(PaymentResultService::class)->apply($order, ['status' => 'rejected', 'id' => '1234567890']);

        $this->assertSame(OrderStatus::PAID, $order->fresh()->status);
    }

    public function test_refund_webhook_still_moves_a_paid_order_to_refunded(): void
    {
        Mail::fake();
        $order = $this->paidOrder();

        app(PaymentResultService::class)->apply($order, ['status' => 'refunded', 'id' => '1234567890']);

        $this->assertSame(OrderStatus::REFUNDED, $order->fresh()->status);
    }

    public function test_refunded_order_is_not_reactivated_by_a_later_approved_webhook(): void
    {
        Mail::fake();
        $order = $this->paidOrder();
        $order->update(['status' => OrderStatus::REFUNDED]);

        app(PaymentResultService::class)->apply($order, ['status' => 'approved', 'id' => '1234567890']);

        $this->assertSame(OrderStatus::REFUNDED, $order->fresh()->status);
    }

    public function test_late_approved_webhook_backfills_missing_fee_and_net_on_paid_order(): void
    {
        Mail::fake();
        Queue::fake();
        $order = $this->paidOrder();
        $order->update(['fee_cents' => null, 'net_amount_cents' => null]);

        app(PaymentResultService::class)->apply($order, [
            'status' => 'approved',
            'id' => '1234567890',
            'transaction_amount' => 100.0,
            'transaction_details' => ['net_received_amount' => 95.0],
        ]);

        $fresh = $order->fresh();
        $this->assertSame(OrderStatus::PAID, $fresh->status);
        $this->assertSame(9500, $fresh->net_amount_cents);
        $this->assertSame(500, $fresh->fee_cents);
        // backfill não deve reprocessar efeitos: nenhum job de ingressos disparado
        Queue::assertNothingPushed();
    }

    public function test_late_approved_webhook_does_not_overwrite_existing_fee_and_net(): void
    {
        Mail::fake();
        $order = $this->paidOrder();
        $order->update(['fee_cents' => 300, 'net_amount_cents' => 9700]);

        app(PaymentResultService::class)->apply($order, [
            'status' => 'approved',
            'id' => '1234567890',
            'transaction_amount' => 100.0,
            'transaction_details' => ['net_received_amount' => 95.0],
        ]);

        $fresh = $order->fresh();
        $this->assertSame(9700, $fresh->net_amount_cents);
        $this->assertSame(300, $fresh->fee_cents);
    }

    public function test_pending_order_still_advances_to_paid_on_approval(): void
    {
        Mail::fake();
        Queue::fake(); // evita rodar GenerateOrderTicketsJob (e o timeout do WhatsApp) inline
        $organizer = Organizer::factory()->create();
        $event = Event::factory()->for($organizer)->create();
        $order = Order::factory()->for($event)->for($organizer)->pending()
            ->state(['payment_id' => '555'])
            ->create();

        app(PaymentResultService::class)->apply($order, ['status' => 'approved', 'id' => '555', 'transaction_amount' => 50.0]);

        $fresh = $order->fresh();
        $this->assertSame(OrderStatus::PAID, $fresh->status);
        $this->assertNotNull($fresh->paid_at);
    }
}
