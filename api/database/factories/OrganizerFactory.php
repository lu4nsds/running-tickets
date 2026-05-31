<?php

namespace Database\Factories;

use App\Enums\OrganizerStatus;
use App\Models\Organizer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organizer>
 */
class OrganizerFactory extends Factory
{
    protected $model = Organizer::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'document' => fake()->numerify('##############'),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('###########'),
            'address' => fake()->streetAddress(),
            'neighborhood' => fake()->word(),
            'city' => fake()->city(),
            'state' => 'SP',
            'zip_code' => fake()->numerify('########'),
            'status' => OrganizerStatus::ACTIVE->value,
        ];
    }
}
