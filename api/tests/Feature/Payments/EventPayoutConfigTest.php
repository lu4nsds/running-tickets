<?php

namespace Tests\Feature\Payments;

use App\Models\AdminUser;
use App\Models\Organizer;
use App\Models\PaymentGatewayAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventPayoutConfigTest extends TestCase
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

    private function eventPayload(Organizer $organizer, array $overrides = []): array
    {
        return array_merge([
            'organizer_id' => $organizer->id,
            'title' => 'Corrida Teste',
            'slug' => 'corrida-teste-'.uniqid(),
            'state' => 'SP',
            'city' => 'São Paulo',
            'venue' => 'Parque',
            'date_start' => now()->addMonth()->format('Y-m-d H:i:s'),
            'date_end' => now()->addMonth()->addHours(4)->format('Y-m-d H:i:s'),
        ], $overrides);
    }

    public function test_split_rejected_when_organizer_not_connected(): void
    {
        $admin = $this->makeSuperAdmin();
        $organizer = Organizer::factory()->create();

        $this->actingAs($admin, 'admin')
            ->postJson('/api/admin/events', $this->eventPayload($organizer, [
                'payout_mode' => 'split',
                'platform_fee_rate' => 0.12,
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['payout_mode']);
    }

    public function test_split_accepted_when_organizer_connected(): void
    {
        $admin = $this->makeSuperAdmin();
        $organizer = Organizer::factory()->create();
        PaymentGatewayAccount::factory()->for($organizer)->create();

        $this->actingAs($admin, 'admin')
            ->postJson('/api/admin/events', $this->eventPayload($organizer, [
                'payout_mode' => 'split',
                'platform_fee_rate' => 0.12,
            ]))
            ->assertStatus(201)
            ->assertJsonPath('data.payout_mode', 'split')
            ->assertJsonPath('data.platform_fee_rate', 0.12);
    }
}
