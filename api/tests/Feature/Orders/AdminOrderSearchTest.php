<?php

namespace Tests\Feature\Orders;

use App\Models\AdminUser;
use App\Models\Event;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Organizer;
use App\Models\TicketType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrderSearchTest extends TestCase
{
    use RefreshDatabase;

    private function makeSuperAdmin(): AdminUser
    {
        $user = AdminUser::factory()->create();
        \DB::table('roles')->insertOrIgnore(['name' => 'Super Admin', 'slug' => 'super_admin', 'created_at' => now(), 'updated_at' => now()]);
        $role = \DB::table('roles')->where('slug', 'super_admin')->first();
        \DB::table('admin_user_roles')->insertOrIgnore(['admin_user_id' => $user->id, 'role_id' => $role->id]);

        return $user->fresh();
    }

    private function makeOrder(Event $event, Organizer $organizer, string $reference, string $email, string $cpf): Order
    {
        $ticketType = TicketType::factory()->for($event)->create(['name' => 'Lote '.uniqid()]);
        $order = Order::factory()->for($event)->for($organizer)->paid()
            ->state(['reference' => $reference, 'buyer_email' => $email])
            ->create();
        OrderItem::factory()->for($order)
            ->state([
                'ticket_type_id' => $ticketType->id,
                'participant_data' => ['name' => 'X', 'email' => $email, 'cpf' => $cpf],
            ])
            ->create();

        return $order;
    }

    public function test_search_by_reference_returns_only_matching_order(): void
    {
        $admin = $this->makeSuperAdmin();
        $organizer = Organizer::factory()->create();
        $event = Event::factory()->for($organizer)->create();

        $a = $this->makeOrder($event, $organizer, 'ORD-2026-AAA111', 'a@test.com', '11111111111');
        $this->makeOrder($event, $organizer, 'ORD-2026-BBB222', 'b@test.com', '22222222222');

        $this->actingAs($admin, 'admin')
            ->getJson("/api/admin/events/{$event->id}/orders?search=AAA111")
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.reference', $a->reference);
    }

    public function test_search_by_email_returns_only_matching_order(): void
    {
        $admin = $this->makeSuperAdmin();
        $organizer = Organizer::factory()->create();
        $event = Event::factory()->for($organizer)->create();

        $this->makeOrder($event, $organizer, 'ORD-2026-AAA111', 'alice@test.com', '11111111111');
        $this->makeOrder($event, $organizer, 'ORD-2026-BBB222', 'bob@test.com', '22222222222');

        $this->actingAs($admin, 'admin')
            ->getJson("/api/admin/events/{$event->id}/orders?search=alice")
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.buyer_email', 'alice@test.com');
    }

    public function test_search_by_cpf_returns_only_matching_order(): void
    {
        $admin = $this->makeSuperAdmin();
        $organizer = Organizer::factory()->create();
        $event = Event::factory()->for($organizer)->create();

        $this->makeOrder($event, $organizer, 'ORD-2026-AAA111', 'a@test.com', '12345678901');
        $this->makeOrder($event, $organizer, 'ORD-2026-BBB222', 'b@test.com', '99999999999');

        $this->actingAs($admin, 'admin')
            ->getJson('/api/admin/events/'.$event->id.'/orders?search=123.456.789-01')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.reference', 'ORD-2026-AAA111');
    }

    public function test_reference_search_with_digit_does_not_broadly_match_cpfs(): void
    {
        // Regressão: dígitos avulsos numa referência não devem casar qualquer CPF.
        $admin = $this->makeSuperAdmin();
        $organizer = Organizer::factory()->create();
        $event = Event::factory()->for($organizer)->create();

        // Referência alvo contém "1"; outros CPFs também contêm "1".
        $this->makeOrder($event, $organizer, 'ORD-2026-AB1CDE', 'a@test.com', '11111111111');
        $this->makeOrder($event, $organizer, 'ORD-2026-ZZ9YYY', 'b@test.com', '12121212121');

        $this->actingAs($admin, 'admin')
            ->getJson("/api/admin/events/{$event->id}/orders?search=AB1CDE")
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.reference', 'ORD-2026-AB1CDE');
    }
}
