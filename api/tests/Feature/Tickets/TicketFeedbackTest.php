<?php

namespace Tests\Feature\Tickets;

use App\Enums\PlatformSettingKey;
use App\Enums\TicketStatus;
use App\Mail\FeedbackRequestMail;
use App\Models\AdminUser;
use App\Models\Event;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Organizer;
use App\Models\PlatformSetting;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Mockery\MockInterface;
use Tests\TestCase;

class TicketFeedbackTest extends TestCase
{
    use RefreshDatabase;

    private const FORM_URL = 'https://forms.gle/feedback123';

    /**
     * Cria um pedido pago com $ticketCount ingressos, todos validados em
     * $validatedAt (null = ainda não validados).
     */
    private function paidOrderWithValidatedTickets(?\DateTimeInterface $validatedAt, int $ticketCount = 1): Order
    {
        $organizer = Organizer::factory()->create();
        $event = Event::factory()->for($organizer)->create();
        $ticketType = TicketType::factory()->for($event)->create();

        $order = Order::factory()->for($event)->for($organizer)->paid()
            ->state([
                'buyer_email' => 'comprador@teste.com',
                'buyer_phone' => '11999998888',
                'paid_at' => now()->subDays(5),
            ])
            ->create();

        for ($i = 0; $i < $ticketCount; $i++) {
            $item = OrderItem::factory()->for($order)
                ->state(['ticket_type_id' => $ticketType->id])
                ->create();

            Ticket::factory()->for($item, 'orderItem')->state([
                'status' => $validatedAt ? TicketStatus::USED : TicketStatus::ACTIVE,
                'validated_at' => $validatedAt,
            ])->create();
        }

        return $order->fresh();
    }

    private function expectWhatsAppSends(int $times): void
    {
        $this->mock(WhatsAppService::class, function (MockInterface $mock) use ($times) {
            $mock->shouldReceive('send')->times($times)->andReturn(true);
        });
    }

    private function configureFormUrl(): void
    {
        PlatformSetting::setValue(PlatformSettingKey::FEEDBACK_FORM_URL, self::FORM_URL);
    }

    public function test_validating_a_ticket_records_validated_at(): void
    {
        $organizer = Organizer::factory()->create();
        $event = Event::factory()->for($organizer)->create();
        $ticketType = TicketType::factory()->for($event)->create();

        $order = Order::factory()->for($event)->for($organizer)->paid()->create();
        $item = OrderItem::factory()->for($order)->state(['ticket_type_id' => $ticketType->id])->create();
        $ticket = Ticket::factory()->for($item, 'orderItem')->create();

        $admin = AdminUser::factory()->create();
        \DB::table('roles')->insertOrIgnore(['name' => 'Super Admin', 'slug' => 'super_admin', 'created_at' => now(), 'updated_at' => now()]);
        $role = \DB::table('roles')->where('slug', 'super_admin')->first();
        \DB::table('admin_user_roles')->insertOrIgnore(['admin_user_id' => $admin->id, 'role_id' => $role->id]);

        $this->actingAs($admin->fresh(), 'admin')
            ->postJson("/api/tickets/{$ticket->code}/validate")
            ->assertStatus(200)
            ->assertJsonPath('valid', true);

        $ticket->refresh();

        $this->assertSame(TicketStatus::USED, $ticket->status);
        $this->assertNotNull($ticket->validated_at);
    }

    public function test_does_not_send_before_the_24h_window(): void
    {
        Mail::fake();
        $this->configureFormUrl();
        $this->expectWhatsAppSends(0);

        $order = $this->paidOrderWithValidatedTickets(now()->subHours(23));

        $this->artisan('tickets:send-feedback')->assertSuccessful();

        Mail::assertNothingQueued();
        $this->assertNull($order->fresh()->feedback_sent_at);
    }

    public function test_sends_email_and_whatsapp_after_the_24h_window(): void
    {
        Mail::fake();
        $this->configureFormUrl();
        $this->expectWhatsAppSends(1);

        $order = $this->paidOrderWithValidatedTickets(now()->subHours(25));

        $this->artisan('tickets:send-feedback')->assertSuccessful();

        Mail::assertQueued(FeedbackRequestMail::class, function (FeedbackRequestMail $mail) use ($order) {
            return $mail->hasTo('comprador@teste.com')
                && $mail->formUrl === self::FORM_URL
                && $mail->order->is($order);
        });

        $this->assertNotNull($order->fresh()->feedback_sent_at);
    }

    public function test_order_with_several_validated_tickets_notifies_the_buyer_once(): void
    {
        Mail::fake();
        $this->configureFormUrl();
        $this->expectWhatsAppSends(1);

        $order = $this->paidOrderWithValidatedTickets(now()->subHours(25), ticketCount: 3);

        $this->artisan('tickets:send-feedback')->assertSuccessful();

        Mail::assertQueuedCount(1);
        $this->assertNotNull($order->fresh()->feedback_sent_at);
    }

    public function test_second_run_does_not_resend(): void
    {
        Mail::fake();
        $this->configureFormUrl();
        $this->expectWhatsAppSends(1);

        $this->paidOrderWithValidatedTickets(now()->subHours(25));

        $this->artisan('tickets:send-feedback')->assertSuccessful();
        $this->artisan('tickets:send-feedback')->assertSuccessful();

        Mail::assertQueuedCount(1);
    }

    public function test_sends_nothing_when_the_form_url_is_not_configured(): void
    {
        Mail::fake();
        $this->expectWhatsAppSends(0);

        $order = $this->paidOrderWithValidatedTickets(now()->subHours(25));

        $this->artisan('tickets:send-feedback')->assertSuccessful();

        Mail::assertNothingQueued();

        // Continua elegível: assim que o link for configurado, o convite sai.
        $this->assertNull($order->fresh()->feedback_sent_at);
    }

    public function test_ignores_orders_outside_the_lookback_window(): void
    {
        Mail::fake();
        $this->configureFormUrl();
        $this->expectWhatsAppSends(0);

        $order = $this->paidOrderWithValidatedTickets(now()->subDays(45));

        $this->artisan('tickets:send-feedback')->assertSuccessful();

        Mail::assertNothingQueued();
        $this->assertNull($order->fresh()->feedback_sent_at);
    }

    public function test_ignores_orders_without_validated_tickets(): void
    {
        Mail::fake();
        $this->configureFormUrl();
        $this->expectWhatsAppSends(0);

        $order = $this->paidOrderWithValidatedTickets(null);

        $this->artisan('tickets:send-feedback')->assertSuccessful();

        Mail::assertNothingQueued();
        $this->assertNull($order->fresh()->feedback_sent_at);
    }
}
