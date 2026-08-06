<?php

namespace Tests\Feature\Payments;

use App\Enums\PayoutMode;
use App\Models\AdminUser;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\PaymentGatewayAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrganizerPaymentVisibilityTest extends TestCase
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

    public function test_shows_connection_and_event_mode_counts_for_connected_organizer(): void
    {
        $admin = $this->makeSuperAdmin();
        $organizer = Organizer::factory()->create();
        PaymentGatewayAccount::factory()->for($organizer)->create();

        Event::factory()->count(2)->for($organizer)->create(['payout_mode' => PayoutMode::SPLIT->value]);
        Event::factory()->count(3)->for($organizer)->create(['payout_mode' => PayoutMode::PLATFORM->value]);

        $this->actingAs($admin, 'admin')
            ->getJson("/api/admin/organizers/{$organizer->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.payment_account.connected', true)
            ->assertJsonPath('data.payment_account.gateway', 'mercadopago')
            ->assertJsonPath('data.events_count', 5)
            ->assertJsonPath('data.split_events_count', 2);
    }

    public function test_payment_account_is_null_when_organizer_never_connected(): void
    {
        $admin = $this->makeSuperAdmin();
        $organizer = Organizer::factory()->create();

        $this->actingAs($admin, 'admin')
            ->getJson("/api/admin/organizers/{$organizer->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.payment_account', null)
            ->assertJsonPath('data.split_events_count', 0);
    }

    public function test_never_leaks_oauth_tokens_to_the_super_admin(): void
    {
        $admin = $this->makeSuperAdmin();
        $organizer = Organizer::factory()->create();
        PaymentGatewayAccount::factory()->for($organizer)->create();

        $response = $this->actingAs($admin, 'admin')
            ->getJson("/api/admin/organizers/{$organizer->id}")
            ->assertStatus(200);

        $body = $response->getContent();
        $this->assertStringNotContainsString('ORG-ACCESS-TOKEN', $body);
        $this->assertStringNotContainsString('ORG-REFRESH-TOKEN', $body);
        $this->assertArrayNotHasKey('access_token', $response->json('data.payment_account'));
    }
}
