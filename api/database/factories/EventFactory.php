<?php

namespace Database\Factories;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Organizer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        $title = fake()->words(3, true);

        return [
            'organizer_id' => Organizer::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::random(6),
            'description' => fake()->paragraph(),
            'city' => fake()->city(),
            'state' => 'SP',
            'venue' => fake()->company(),
            'date_start' => now()->addDays(30),
            'date_end' => now()->addDays(31),
            'results_url' => null,
            'status' => EventStatus::ACTIVE->value,
            'allows_late_refund_request' => false,
            'shows_ticket_progress' => false,
        ];
    }

    public function allowsLateRefundRequest(): static
    {
        return $this->state(fn () => ['allows_late_refund_request' => true]);
    }

    public function showsTicketProgress(): static
    {
        return $this->state(fn () => ['shows_ticket_progress' => true]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => EventStatus::INACTIVE->value]);
    }

    public function finished(): static
    {
        return $this->state(fn () => [
            'status' => EventStatus::FINISHED->value,
            'date_start' => now()->subDays(31),
            'date_end' => now()->subDays(30),
        ]);
    }
}
