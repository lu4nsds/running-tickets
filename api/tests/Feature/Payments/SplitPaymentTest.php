<?php

namespace Tests\Feature\Payments;

use App\Enums\OrderStatus;
use App\Enums\PayoutMode;
use App\Jobs\ProcessCardPaymentJob;
use App\Models\Category;
use App\Models\Event;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Organizer;
use App\Models\PaymentGatewayAccount;
use App\Models\TicketType;
use App\Services\MercadoPagoService;
use App\Services\Payment\MercadoPagoCredentialResolver;
use App\Services\PaymentResultService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SplitPaymentTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrder(PayoutMode $mode, bool $connected, int $priceCents = 5000): Order
    {
        $organizer = Organizer::factory()->create();

        if ($connected) {
            PaymentGatewayAccount::factory()->for($organizer)->create();
        }

        $event = Event::factory()->for($organizer)->create([
            'payout_mode' => $mode->value,
            'platform_fee_rate' => 0.10,
        ]);
        $category = Category::factory()->for($event)->create();
        $ticketType = TicketType::factory()->for($event)->create(['price_cents' => $priceCents]);

        $order = Order::factory()->for($event)->for($organizer)->state([
            'status' => OrderStatus::PROCESSING,
            'total_cents' => $priceCents,
            'reserved_until' => now()->addMinutes(15),
        ])->create();

        OrderItem::factory()->for($order)->state([
            'ticket_type_id' => $ticketType->id,
            'category_id' => $category->id,
        ])->create();

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

    private function runCardJob(Order $order, array $captured): void
    {
        (new ProcessCardPaymentJob($order, $this->cardPayload()))->handle(
            app(MercadoPagoService::class),
            app(PaymentResultService::class),
            app(\App\Services\OrderService::class),
            app(MercadoPagoCredentialResolver::class),
        );
    }

    public function test_split_order_sends_organizer_token_and_application_fee(): void
    {
        Bus::fake();
        Mail::fake();

        $order = $this->makeOrder(PayoutMode::SPLIT, connected: true, priceCents: 5000);

        $captured = [];
        $this->mock(MercadoPagoService::class, function ($mock) use (&$captured) {
            $mock->shouldReceive('createCardPayment')
                ->andReturnUsing(function (...$args) use (&$captured) {
                    $captured['accessToken'] = $args[6];
                    $captured['applicationFee'] = $args[7];

                    return [
                        'id' => 555,
                        'status' => 'approved',
                        'status_detail' => 'accredited',
                        'transaction_amount' => 50.00,
                        'transaction_details' => ['net_received_amount' => 44.00],
                        'payment_method_id' => 'visa',
                        'payment_type_id' => 'credit_card',
                        'installments' => 1,
                    ];
                });
        });

        $this->runCardJob($order, $captured);

        $this->assertSame('ORG-ACCESS-TOKEN', $captured['accessToken']);
        $this->assertSame(5.0, $captured['applicationFee']); // 10% de R$50,00

        $order->refresh();
        $this->assertSame('split', $order->settlement_mode);
        $this->assertSame(500, $order->application_fee_cents);
        // fee_cents = retido pelo MP (600) menos application_fee (500) = 100
        $this->assertSame(100, $order->fee_cents);
        $this->assertSame(4400, $order->net_amount_cents); // líquido do organizador
    }

    public function test_platform_order_sends_no_application_fee(): void
    {
        Bus::fake();
        Mail::fake();
        config(['mercadopago.access_token' => 'PLATFORM-TOKEN']);

        $order = $this->makeOrder(PayoutMode::PLATFORM, connected: false, priceCents: 5000);

        $captured = [];
        $this->mock(MercadoPagoService::class, function ($mock) use (&$captured) {
            $mock->shouldReceive('createCardPayment')
                ->andReturnUsing(function (...$args) use (&$captured) {
                    $captured['accessToken'] = $args[6];
                    $captured['applicationFee'] = $args[7];

                    return [
                        'id' => 556,
                        'status' => 'approved',
                        'status_detail' => 'accredited',
                        'transaction_amount' => 50.00,
                        'transaction_details' => ['net_received_amount' => 48.50],
                        'payment_method_id' => 'visa',
                        'payment_type_id' => 'credit_card',
                        'installments' => 1,
                    ];
                });
        });

        $this->runCardJob($order, $captured);

        $this->assertSame('PLATFORM-TOKEN', $captured['accessToken']);
        $this->assertNull($captured['applicationFee']);

        $order->refresh();
        $this->assertSame('platform', $order->settlement_mode);
        $this->assertNull($order->application_fee_cents);
        $this->assertSame(150, $order->fee_cents); // (50 - 48.5) * 100
    }

    public function test_resolver_throws_when_split_organizer_not_connected(): void
    {
        $order = $this->makeOrder(PayoutMode::SPLIT, connected: false);

        $this->expectException(\RuntimeException::class);

        app(MercadoPagoCredentialResolver::class)->resolveForOrder($order);
    }

    public function test_event_effective_fee_rate_falls_back_to_config_for_old_events(): void
    {
        config(['platform.fee_rate' => 0.10]);

        $withRate = Event::factory()->create(['platform_fee_rate' => 0.15]);
        $withoutRate = Event::factory()->create(['platform_fee_rate' => null]);

        $this->assertSame(0.15, $withRate->effectiveFeeRate());
        $this->assertSame(0.10, $withoutRate->effectiveFeeRate());
    }

    public function test_webhook_uses_organizer_token_for_split_order(): void
    {
        Bus::fake();
        Mail::fake();

        $order = $this->makeOrder(PayoutMode::SPLIT, connected: true);
        $order->update(['payment_id' => '777', 'settlement_mode' => 'split']);

        $captured = [];
        $this->mock(MercadoPagoService::class, function ($mock) use (&$captured) {
            $mock->shouldReceive('getPaymentById')
                ->andReturnUsing(function (...$args) use (&$captured) {
                    $captured['token'] = $args[1] ?? null;

                    return [
                        'id' => 777,
                        'status' => 'approved',
                        'status_detail' => 'accredited',
                        'external_reference' => $args[0],
                        'transaction_amount' => 50.00,
                        'transaction_details' => ['net_received_amount' => 44.00],
                        'payment_method_id' => 'visa',
                        'payment_type_id' => 'credit_card',
                        'installments' => 1,
                    ];
                });
        });

        $this->postJson('/api/webhooks/mercadopago', [
            'type' => 'payment',
            'data' => ['id' => '777'],
        ])->assertStatus(200);

        $this->assertSame('ORG-ACCESS-TOKEN', $captured['token']);
    }
}
