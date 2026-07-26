<?php

namespace Tests\Feature\Payments;

use App\Models\Organizer;
use App\Models\PaymentGatewayAccount;
use App\Services\Payment\MercadoPagoOAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MercadoPagoOAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_exchange_code_normalizes_response_and_persists_account(): void
    {
        config([
            'mercadopago.oauth.app_id' => 'APP123',
            'mercadopago.oauth.client_secret' => 'SECRET',
            'mercadopago.oauth.redirect_uri' => 'https://api.test/callback',
            'mercadopago.oauth.token_url' => 'https://api.mercadopago.com/oauth/token',
        ]);

        Http::fake([
            'https://api.mercadopago.com/oauth/token' => Http::response([
                'access_token' => 'ORG-AT',
                'refresh_token' => 'ORG-RT',
                'public_key' => 'ORG-PK',
                'user_id' => 987654,
                'expires_in' => 15552000,
                'scope' => 'read write',
            ], 200),
        ]);

        $organizer = Organizer::factory()->create();
        $service = app(MercadoPagoOAuthService::class);

        $tokens = $service->exchangeCode('AUTH-CODE');
        $account = $service->storeAccount($organizer, $tokens);

        $this->assertSame('ORG-AT', $tokens['access_token']);
        $this->assertSame('987654', $tokens['provider_account_id']);

        $this->assertDatabaseHas('payment_gateway_accounts', [
            'organizer_id' => $organizer->id,
            'gateway' => 'mercadopago',
            'provider_account_id' => '987654',
            'public_key' => 'ORG-PK',
            'status' => PaymentGatewayAccount::STATUS_CONNECTED,
        ]);

        // Token guardado criptografado (não em texto puro na coluna).
        $raw = \DB::table('payment_gateway_accounts')->where('organizer_id', $organizer->id)->value('access_token');
        $this->assertNotSame('ORG-AT', $raw);
        $this->assertSame('ORG-AT', $account->fresh()->access_token);
    }
}
