<?php

namespace Database\Factories;

use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        return [
            'participant_data' => [
                'name' => fake()->name(),
                'email' => fake()->safeEmail(),
                'cpf' => fake()->numerify('###########'),
                'phone' => fake()->numerify('###########'),
                'birthdate' => '1990-01-01',
            ],
        ];
    }
}
