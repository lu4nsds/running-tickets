<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\TicketType;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketTypeFactory extends Factory
{
    protected $model = TicketType::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'name' => fake()->randomElement(['Lote 1', 'Lote 2', 'Lote Promocional']),
            'description' => fake()->sentence(),
            'price_cents' => 5000,
            'currency' => 'BRL',
            'quota' => 100,
            'start_sale' => now()->subDay(),
            'end_sale' => now()->addDays(20),
            'active' => true,
        ];
    }

    public function withQuota(int $quota): static
    {
        return $this->state(fn () => ['quota' => $quota]);
    }
}
