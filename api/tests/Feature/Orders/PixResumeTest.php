<?php

namespace Tests\Feature\Orders;

use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Event;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Organizer;
use App\Models\TicketType;
use App\Services\MercadoPagoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PixResumeTest extends TestCase
{
    use RefreshDatabase;

    private function makePendingOrder(?array $pixBody = null): Order
    {
        $organizer = Organizer::factory()->create();
        $event = Event::factory()->for($organizer)->create();
        Category::factory()->for($event)->create();
        $ticketType = TicketType::factory()->for($event)->create();

        $order = Order::factory()->for($event)->for($organizer)
            ->state([
                'status' => OrderStatus::PENDING,
                'reserved_until' => now()->addMinutes(10),
                'payment_response_body' => $pixBody,
            ])
            ->create();

        OrderItem::factory()->for($order)
            ->state(['ticket_type_id' => $ticketType->id])
            ->create();

        return $order->fresh()->load('items');
    }

    private function pixPayload(): array
    {
        return [
            'payment_method' => 'pix',
            'payer' => [
                'email' => 'buyer@test.com',
                'phone' => '11999990000',
                'identification' => ['type' => 'CPF', 'number' => '12345678901'],
            ],
        ];
    }

    public function test_pix_creation_persists_qr_in_payment_response_body(): void
    {
        $order = $this->makePendingOrder();

        $this->mock(MercadoPagoService::class, function ($mock) {
            $mock->shouldReceive('createPixPayment')->once()->andReturn([
                'id' => 12345,
                'status' => 'pending',
                'status_detail' => 'pending_waiting_transfer',
                'transaction_amount' => 50.00,
                'point_of_interaction' => [
                    'transaction_data' => [
                        'qr_code' => 'PIXCODE-ABC',
                        'qr_code_base64' => 'BASE64==',
                        'ticket_url' => 'https://mp.example/pix/12345',
                    ],
                ],
            ]);
        });

        $this->postJson("/api/orders/{$order->reference}/payment", $this->pixPayload())
            ->assertStatus(200)
            ->assertJsonPath('pix.qr_code', 'PIXCODE-ABC');

        $body = $order->fresh()->payment_response_body;
        $this->assertSame('PIXCODE-ABC', $body['qr_code']);
        $this->assertSame('pending', $body['outcome']);
    }

    public function test_status_endpoint_returns_saved_pix(): void
    {
        $order = $this->makePendingOrder([
            'outcome' => 'pending',
            'mp_payment_id' => '999',
            'mp_status' => 'pending',
            'qr_code' => 'PIXCODE-SAVED',
            'qr_code_base64' => 'BASE64==',
            'ticket_url' => 'https://mp.example/pix/999',
        ]);

        $response = $this->getJson("/api/orders/{$order->reference}/status");

        $response->assertStatus(200)
            ->assertJsonPath('pix.qr_code', 'PIXCODE-SAVED')
            ->assertJsonPath('is_payable', true);
    }

    public function test_retry_with_pending_pix_does_not_call_mp_again(): void
    {
        $order = $this->makePendingOrder([
            'outcome' => 'pending',
            'mp_payment_id' => '888',
            'mp_status' => 'pending',
            'qr_code' => 'PIXCODE-EXISTING',
            'qr_code_base64' => 'BASE64==',
            'ticket_url' => 'https://mp.example/pix/888',
        ]);

        $this->mock(MercadoPagoService::class, function ($mock) {
            $mock->shouldNotReceive('createPixPayment');
        });

        $this->postJson("/api/orders/{$order->reference}/payment", $this->pixPayload())
            ->assertStatus(200)
            ->assertJsonPath('pix.qr_code', 'PIXCODE-EXISTING');
    }

    public function test_expired_reservation_blocks_pix_retry(): void
    {
        $order = $this->makePendingOrder();
        $order->update(['reserved_until' => now()->subMinute()]);

        $this->postJson("/api/orders/{$order->reference}/payment", $this->pixPayload())
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'reservation_expired');
    }
}
