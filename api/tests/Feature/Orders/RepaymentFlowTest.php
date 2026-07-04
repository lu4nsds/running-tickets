<?php

namespace Tests\Feature\Orders;

use App\Enums\OrderStatus;
use App\Jobs\ProcessCardPaymentJob;
use App\Mail\PaymentFailedMail;
use App\Models\Category;
use App\Models\Event;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Organizer;
use App\Models\TicketType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RepaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    private function makeFailedOrderWithValidReservation(): Order
    {
        $organizer = Organizer::factory()->create();
        $event = Event::factory()->for($organizer)->create();
        Category::factory()->for($event)->create();
        $ticketType = TicketType::factory()->for($event)->create();

        $order = Order::factory()->for($event)->for($organizer)
            ->state([
                'status' => OrderStatus::FAILED,
                'reserved_until' => now()->addMinutes(10),
                'payment_response_body' => [
                    'outcome' => 'failed',
                    'failure_reason' => 'card_declined',
                ],
            ])
            ->create();

        OrderItem::factory()->for($order)
            ->state(['ticket_type_id' => $ticketType->id])
            ->create();

        return $order->fresh()->load('items');
    }

    private function cardPayload(): array
    {
        return [
            'payment_method' => 'credit_card',
            'token' => 'tok-retry',
            'payment_method_id' => 'visa',
            'installments' => 1,
            'payer' => [
                'email' => 'buyer@test.com',
                'phone' => '11999990000',
                'identification' => ['type' => 'CPF', 'number' => '12345678901'],
            ],
        ];
    }

    public function test_failed_order_with_active_reservation_accepts_retry(): void
    {
        Bus::fake();

        $order = $this->makeFailedOrderWithValidReservation();

        $response = $this->postJson("/api/orders/{$order->reference}/payment", $this->cardPayload());

        $response->assertStatus(202)->assertJsonPath('payment_status', 'processing');

        Bus::assertDispatched(ProcessCardPaymentJob::class);
        $this->assertSame(OrderStatus::PROCESSING, $order->fresh()->status);
    }

    public function test_expired_reservation_returns_422_with_reservation_expired_code(): void
    {
        $order = $this->makeFailedOrderWithValidReservation();
        $order->update(['reserved_until' => now()->subMinute()]);

        $response = $this->postJson("/api/orders/{$order->reference}/payment", $this->cardPayload());

        $response->assertStatus(422)->assertJsonPath('error_code', 'reservation_expired');
    }

    public function test_failure_email_contains_repayment_link(): void
    {
        Mail::fake();

        $order = $this->makeFailedOrderWithValidReservation();

        Mail::to($order->buyer_email)->send(new PaymentFailedMail($order));

        Mail::assertSent(PaymentFailedMail::class, function ($mail) use ($order) {
            $rendered = $mail->render();

            return str_contains($rendered, "/pagamento/{$order->reference}");
        });
    }

    public function test_status_endpoint_exposes_outcome_and_failure_fields(): void
    {
        $order = $this->makeFailedOrderWithValidReservation();
        $order->update([
            'payment_response_body' => [
                'outcome' => 'failed',
                'failure_reason' => 'card_declined',
                'failure_message_pt' => 'Cartão recusado pelo emissor.',
            ],
        ]);

        $response = $this->getJson("/api/orders/{$order->reference}/status");

        $response->assertStatus(200)
            ->assertJsonPath('outcome', 'failed')
            ->assertJsonPath('failure_reason', 'card_declined')
            ->assertJsonPath('failure_message_pt', 'Cartão recusado pelo emissor.')
            ->assertJsonPath('is_payable', true);
    }
}
