<?php

namespace Tests\Feature\WhatsApp;

use App\Enums\OrderStatus;
use App\Enums\TicketStatus;
use App\Jobs\GenerateOrderTicketsJob;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Ticket;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendWhatsAppNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_sends_to_buyer_and_participants(): void
    {
        Mail::fake();

        $order = $this->makeOrderWithTickets(
            buyerPhone: '11900000000',
            participants: [
                ['name' => 'Ana Silva',  'phone' => '11911111111'],
                ['name' => 'João Souza', 'phone' => '11922222222'],
            ]
        );

        $sentTo = [];

        $whatsApp = $this->mock(WhatsAppService::class, function ($mock) use (&$sentTo) {
            $mock->shouldReceive('send')
                ->andReturnUsing(function (string $phone, string $message) use (&$sentTo) {
                    $sentTo[] = $phone;

                    return true;
                });
        });

        (new GenerateOrderTicketsJob($order))->handle($whatsApp);

        // Comprador + 2 participantes (ambos com telefone diferente do comprador)
        $this->assertCount(3, $sentTo);
        $this->assertContains('11900000000', $sentTo);
        $this->assertContains('11911111111', $sentTo);
        $this->assertContains('11922222222', $sentTo);
    }

    public function test_job_skips_participant_when_phone_equals_buyer_phone(): void
    {
        Mail::fake();

        $order = $this->makeOrderWithTickets(
            buyerPhone: '11911111111',
            participants: [
                ['name' => 'Ana Silva', 'phone' => '11911111111'], // mesmo número do comprador
            ]
        );

        $sentTo = [];

        $whatsApp = $this->mock(WhatsAppService::class, function ($mock) use (&$sentTo) {
            $mock->shouldReceive('send')
                ->andReturnUsing(function (string $phone) use (&$sentTo) {
                    $sentTo[] = $phone;

                    return true;
                });
        });

        (new GenerateOrderTicketsJob($order))->handle($whatsApp);

        // Só o comprador — participante tem o mesmo telefone
        $this->assertCount(1, $sentTo);
    }

    public function test_generate_order_tickets_job_calls_whatsapp_send(): void
    {
        Mail::fake();

        $order = $this->makeOrderWithTickets(
            buyerPhone: '11900000000',
            participants: [['name' => 'Ana', 'phone' => '11911111111']],
        );

        $whatsApp = $this->mock(WhatsAppService::class, function ($mock) {
            $mock->shouldReceive('send')->twice()->andReturn(true);
        });

        (new GenerateOrderTicketsJob($order))->handle($whatsApp);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function makeOrderWithTickets(string $buyerPhone, array $participants): Order
    {
        $organizer = \App\Models\Organizer::factory()->create();
        $event = \App\Models\Event::factory()->for($organizer)->create([
            'title' => 'Corrida de Teste',
            'date_start' => now()->addDays(30),
            'city' => 'São Paulo',
            'state' => 'SP',
            'venue' => 'Parque Ibirapuera',
        ]);

        $order = Order::factory()->create([
            'organizer_id' => $organizer->id,
            'event_id' => $event->id,
            'buyer_phone' => $buyerPhone,
            'buyer_email' => 'comprador@test.com',
            'status' => OrderStatus::PAID,
        ]);

        foreach ($participants as $participant) {
            $item = OrderItem::factory()->create([
                'order_id' => $order->id,
                'participant_data' => ['name' => $participant['name'], 'phone' => $participant['phone'], 'email' => 'p@test.com', 'cpf' => '12345678901'],
            ]);

            Ticket::factory()->create([
                'order_item_id' => $item->id,
                'status' => TicketStatus::ACTIVE,
            ]);
        }

        return $order->fresh();
    }
}
