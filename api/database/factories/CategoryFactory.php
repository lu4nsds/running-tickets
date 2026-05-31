<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'name' => fake()->randomElement(['Geral', 'Masculino', 'Feminino']),
            'distance' => fake()->randomElement([5.00, 10.00, 21.00]),
            'gender' => fake()->randomElement(['M', 'F', 'X']),
            'min_age' => 16,
            'max_age' => 99,
            'active' => true,
        ];
    }
}
