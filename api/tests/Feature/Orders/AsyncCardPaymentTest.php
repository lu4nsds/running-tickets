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
use App\Services\MercadoPagoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AsyncCardPaymentTest extends TestCase
{
    use RefreshDatabase;

    private function makePayableOrder(array $orderState = []): Order
    {
        $organizer = Organizer::factory()->create();
        $event = Event::factory()->for($organizer)->create();
        $category = Category::factory()->for($event)->create();
        $ticketType = TicketType::factory()->for($event)->create();

        $order = Order::factory()
            ->for($event)
            ->for($organizer)
            ->state(array_merge([
                'status' => OrderStatus::PENDING,
                'total_cents' => $ticketType->price_cents,
                'reserved_until' => now()->addMinutes(15),
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

    private function cardPayload(): array
    {
        return [
            'payment_method' => 'credit_card',
            'token' => 'mock-token',
            'payment_method_id' => 'visa',
            'installments' => 1,
            'payer' => [
                'email' => 'buyer@test.com',
                'phone' => '11999990000',
                'identification' => ['type' => 'CPF', 'number' => '12345678901'],
            ],
        ];
    }

    public function test_card_payment_returns_202_and_dispatches_job_to_payments_queue(): void
    {
        Bus::fake();

        $order = $this->makePayableOrder();

        $response = $this->postJson("/api/orders/{$order->reference}/payment", $this->cardPayload());

        $response->assertStatus(202)->assertJsonPath('payment_status', 'processing');

        Bus::assertDispatched(ProcessCardPaymentJob::class, function (ProcessCardPaymentJob $job) use ($order) {
            return $job->order->id === $order->id && $job->queue === 'payments';
        });

        $this->assertSame(OrderStatus::PROCESSING, $order->fresh()->status);
    }

    public function test_approved_card_marks_order_paid_with_payment_response_body(): void
    {
        Mail::fake();
        Bus::fake(); // evita rodar GenerateOrderTicketsJob (WhatsApp/PDF) inline

        $order = $this->makePayableOrder(['status' => OrderStatus::PROCESSING]);

        $this->mock(MercadoPagoService::class, function ($mock) {
            $mock->shouldReceive('createCardPayment')->andReturn([
                'id' => 12345,
                'status' => 'approved',
                'status_detail' => 'accredited',
                'transaction_amount' => 50.00,
                'transaction_details' => ['net_received_amount' => 48.5, 'total_paid_amount' => 50.0],
                'payment_method_id' => 'visa',
                'payment_type_id' => 'credit_card',
                'installments' => 1,
            ]);
        });

        (new ProcessCardPaymentJob($order, $this->cardPayload()))->handle(
            app(MercadoPagoService::class),
            app(\App\Services\PaymentResultService::class),
            app(\App\Services\OrderService::class),
        );

        $order->refresh();
        $this->assertSame(OrderStatus::PAID, $order->status);
        $this->assertSame('approved', $order->payment_response_body['outcome']);
        $this->assertSame('12345', $order->payment_response_body['mp_payment_id']);
        $this->assertSame(150, $order->fee_cents); // (50 - 48.5) * 100
    }

    public function test_rejected_card_marks_order_failed_and_sends_email(): void
    {
        Mail::fake();

        $order = $this->makePayableOrder(['status' => OrderStatus::PROCESSING]);

        $this->mock(MercadoPagoService::class, function ($mock) {
            $mock->shouldReceive('createCardPayment')->andReturn([
                'id' => 99999,
                'status' => 'rejected',
                'status_detail' => 'cc_rejected_insufficient_amount',
                'transaction_amount' => 50.00,
                'transaction_details' => [],
                'payment_method_id' => 'visa',
                'payment_type_id' => 'credit_card',
                'installments' => 1,
            ]);
        });

        (new ProcessCardPaymentJob($order, $this->cardPayload()))->handle(
            app(MercadoPagoService::class),
            app(\App\Services\PaymentResultService::class),
            app(\App\Services\OrderService::class),
        );

        $order->refresh();
        $this->assertSame(OrderStatus::FAILED, $order->status);
        $this->assertSame('card_declined', $order->payment_response_body['failure_reason']);
        $this->assertStringContainsString('saldo insuficiente', $order->payment_response_body['failure_message_pt']);

        Mail::assertSent(PaymentFailedMail::class, fn ($mail) => $mail->order->id === $order->id);
    }

    public function test_job_failed_marks_order_failed_with_token_expired_reason(): void
    {
        Mail::fake();

        $order = $this->makePayableOrder(['status' => OrderStatus::PROCESSING]);

        $job = new ProcessCardPaymentJob($order, $this->cardPayload());
        $job->failed(new \Exception('card_token has expired'));

        $order->refresh();
        $this->assertSame(OrderStatus::FAILED, $order->status);
        $this->assertSame('internal_error', $order->payment_response_body['failure_reason']);

        Mail::assertSent(PaymentFailedMail::class);
    }

    public function test_pix_remains_synchronous_and_does_not_dispatch_payments_job(): void
    {
        Bus::fake();

        $order = $this->makePayableOrder();

        $this->mock(MercadoPagoService::class, function ($mock) {
            $mock->shouldReceive('createPixPayment')->andReturn([
                'id' => 7777,
                'status' => 'pending',
                'status_detail' => 'pending_waiting_transfer',
                'transaction_amount' => 50.00,
                'point_of_interaction' => [
                    'transaction_data' => [
                        'qr_code' => '00020126...',
                        'qr_code_base64' => 'BASE64==',
                        'ticket_url' => 'https://mp.example/pix/7777',
                    ],
                ],
            ]);
        });

        $payload = [
            'payment_method' => 'pix',
            'payer' => [
                'email' => 'buyer@test.com',
                'phone' => '11999990000',
                'identification' => ['type' => 'CPF', 'number' => '12345678901'],
            ],
        ];

        $response = $this->postJson("/api/orders/{$order->reference}/payment", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('payment_status', 'pending')
            ->assertJsonPath('pix.qr_code', '00020126...');

        Bus::assertNotDispatched(ProcessCardPaymentJob::class);

        $order->refresh();
        $this->assertSame('pending', $order->payment_response_body['outcome']);
        $this->assertSame('00020126...', $order->payment_response_body['qr_code']);
    }
}
