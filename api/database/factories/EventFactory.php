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
            'slug' => Str::slug($title) . '-' . Str::random(6),
            'description' => fake()->paragraph(),
            'city' => fake()->city(),
            'state' => 'SP',
            'venue' => fake()->company(),
            'date_start' => now()->addDays(30),
            'date_end' => now()->addDays(31),
            'status' => EventStatus::ATIVO->value,
        ];
    }

    public function inativo(): static
    {
        return $this->state(fn () => ['status' => EventStatus::INATIVO->value]);
    }
}
