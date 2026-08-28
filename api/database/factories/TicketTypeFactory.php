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
            // Sequencial: ticket_types tem unique(event_id, name), e sortear de uma
            // lista curta fazia dois lotes do mesmo evento colidirem esporadicamente.
            'name' => 'Lote '.fake()->unique()->numberBetween(1, 999999),
            'description' => fake()->sentence(),
            'price_cents' => 5000,
            'currency' => 'BRL',
            'quota' => 100,
            'start_sale' => now()->subDay(),
            'end_sale' => now()->addDays(20),
            'active' => true,
            // Explícitos para que o modelo em memória bata com o default do banco —
            // sem isso a instância recém-criada devolve null nessas flags.
            'allows_shirt_size' => false,
            'requires_senior_age' => false,
            'senior_min_age' => 60,
        ];
    }

    public function withQuota(int $quota): static
    {
        return $this->state(fn () => ['quota' => $quota]);
    }

    /**
     * Lote exclusivo para idosos.
     */
    public function senior(int $minAge = 60): static
    {
        return $this->state(fn () => [
            'requires_senior_age' => true,
            'senior_min_age' => $minAge,
        ]);
    }
}
