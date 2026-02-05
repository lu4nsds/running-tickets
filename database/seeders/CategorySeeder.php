<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Event;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // Segurança: só roda em ambiente local
        if (!app()->environment('local')) {
            return;
        }

        $event = Event::where('slug', 'corrida-dev-5k')->first();

        if (!$event) {
            return;
        }

        $categories = [
            [
                'name' => 'Masculino Geral',
                'gender' => 'M',
                'min_age' => null,
                'max_age' => null,
            ],
            [
                'name' => 'Feminino Geral',
                'gender' => 'F',
                'min_age' => null,
                'max_age' => null,
            ],
            [
                'name' => 'Masculino 18-29',
                'gender' => 'M',
                'min_age' => 18,
                'max_age' => 29,
            ],
            [
                'name' => 'Feminino 18-29',
                'gender' => 'F',
                'min_age' => 18,
                'max_age' => 29,
            ],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                [
                    'event_id' => $event->id,
                    'name' => $category['name'],
                ],
                [
                    'gender' => $category['gender'],
                    'min_age' => $category['min_age'],
                    'max_age' => $category['max_age'],
                    'active' => true,
                ]
            );
        }
    }
}
