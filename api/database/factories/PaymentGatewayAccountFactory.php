<?php

namespace Database\Factories;

use App\Models\Organizer;
use App\Models\PaymentGatewayAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentGatewayAccountFactory extends Factory
{
    protected $model = PaymentGatewayAccount::class;

    public function definition(): array
    {
        return [
            'organizer_id' => Organizer::factory(),
            'gateway' => 'mercadopago',
            'provider_account_id' => (string) $this->faker->numberBetween(100000000, 999999999),
            'access_token' => 'ORG-ACCESS-TOKEN',
            'refresh_token' => 'ORG-REFRESH-TOKEN',
            'public_key' => 'ORG-PUBLIC-KEY',
            'expires_at' => now()->addMonths(6),
            'scopes' => 'read write',
            'status' => PaymentGatewayAccount::STATUS_CONNECTED,
            'connected_at' => now(),
        ];
    }

    public function disconnected(): static
    {
        return $this->state(fn () => [
            'status' => PaymentGatewayAccount::STATUS_REVOKED,
            'access_token' => null,
        ]);
    }
}
