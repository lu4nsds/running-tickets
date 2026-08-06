<?php

namespace Tests\Feature\Payments;

use App\Enums\OrganizerRole;
use App\Models\AdminUser;
use App\Models\Organizer;
use App\Models\PaymentGatewayAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizerPaymentAccountTest extends TestCase
{
    use RefreshDatabase;

    private function makeMember(Organizer $organizer, OrganizerRole $role): AdminUser
    {
        $user = AdminUser::factory()->create();
        $organizer->users()->attach($user->id, ['role' => $role->value]);

        return $user->fresh();
    }

    public function test_organizer_admin_sees_connection_status(): void
    {
        $organizer = Organizer::factory()->create();
        PaymentGatewayAccount::factory()->for($organizer)->create();
        $admin = $this->makeMember($organizer, OrganizerRole::ADMIN);

        $this->actingAs($admin, 'admin')
            ->getJson('/api/organizer/payment-account')
            ->assertStatus(200)
            ->assertJsonPath('account.connected', true)
            ->assertJsonPath('account.gateway', 'mercadopago');
    }

    public function test_status_response_never_leaks_tokens(): void
    {
        $organizer = Organizer::factory()->create();
        PaymentGatewayAccount::factory()->for($organizer)->create();
        $admin = $this->makeMember($organizer, OrganizerRole::ADMIN);

        $response = $this->actingAs($admin, 'admin')
            ->getJson('/api/organizer/payment-account')
            ->assertStatus(200);

        $body = $response->getContent();
        $this->assertStringNotContainsString('ORG-ACCESS-TOKEN', $body);
        $this->assertStringNotContainsString('ORG-REFRESH-TOKEN', $body);
        $this->assertArrayNotHasKey('access_token', $response->json('account'));
    }

    public function test_organizer_staff_cannot_connect(): void
    {
        $organizer = Organizer::factory()->create();
        $staff = $this->makeMember($organizer, OrganizerRole::STAFF);

        $this->actingAs($staff, 'admin')
            ->postJson('/api/organizer/payment-account/connect')
            ->assertStatus(403);
    }

    public function test_organizer_staff_cannot_disconnect(): void
    {
        $organizer = Organizer::factory()->create();
        PaymentGatewayAccount::factory()->for($organizer)->create();
        $staff = $this->makeMember($organizer, OrganizerRole::STAFF);

        $this->actingAs($staff, 'admin')
            ->deleteJson('/api/organizer/payment-account')
            ->assertStatus(403);

        $this->assertDatabaseCount('payment_gateway_accounts', 1);
    }

    public function test_organizer_admin_connect_returns_authorization_url(): void
    {
        config([
            'mercadopago.oauth.app_id' => 'APP123',
            'mercadopago.oauth.redirect_uri' => 'https://api.test/callback',
            'mercadopago.oauth.authorize_url' => 'https://auth.mercadopago.com.br/authorization',
        ]);

        $organizer = Organizer::factory()->create();
        $admin = $this->makeMember($organizer, OrganizerRole::ADMIN);

        $response = $this->actingAs($admin, 'admin')
            ->postJson('/api/organizer/payment-account/connect')
            ->assertStatus(200);

        $url = $response->json('authorization_url');
        $this->assertStringContainsString('client_id=APP123', $url);
        $this->assertStringContainsString('response_type=code', $url);
        $this->assertStringContainsString('state=', $url);
    }

    public function test_organizer_admin_can_disconnect(): void
    {
        $organizer = Organizer::factory()->create();
        PaymentGatewayAccount::factory()->for($organizer)->create();
        $admin = $this->makeMember($organizer, OrganizerRole::ADMIN);

        $this->actingAs($admin, 'admin')
            ->deleteJson('/api/organizer/payment-account')
            ->assertStatus(200);

        $this->assertDatabaseCount('payment_gateway_accounts', 0);
    }

    public function test_callback_with_invalid_state_does_not_create_account(): void
    {
        $this->get('/api/organizer/payment-account/callback?code=abc&state=forjado')
            ->assertRedirect();

        $this->assertDatabaseCount('payment_gateway_accounts', 0);
    }
}
