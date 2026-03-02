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

        $events = Event::all();

        $categoryTemplates = [
            ['name' => 'Masculino 5K', 'distance' => 5, 'description' => 'Corrida de 5km categoria masculina', 'gender' => 'M', 'min_age' => null, 'max_age' => null],
            ['name' => 'Feminino 5K', 'distance' => 5, 'description' => 'Corrida de 5km categoria feminina', 'gender' => 'F', 'min_age' => null, 'max_age' => null],
            ['name' => 'Masculino 10K', 'distance' => 10, 'description' => 'Corrida de 10km categoria masculina', 'gender' => 'M', 'min_age' => 18, 'max_age' => null],
            ['name' => 'Feminino 10K', 'distance' => 10, 'description' => 'Corrida de 10km categoria feminina', 'gender' => 'F', 'min_age' => 18, 'max_age' => null],
            ['name' => 'Masculino 21K', 'distance' => 21, 'description' => 'Meia maratona masculina', 'gender' => 'M', 'min_age' => 18, 'max_age' => null],
            ['name' => 'Feminino 21K', 'distance' => 21, 'description' => 'Meia maratona feminina', 'gender' => 'F', 'min_age' => 18, 'max_age' => null],
        ];

        foreach ($events as $event) {
            foreach ($categoryTemplates as $category) {
                Category::firstOrCreate(
                    [
                        'event_id' => $event->id,
                        'name' => $category['name'],
                    ],
                    [
                        'distance' => $category['distance'],
                        'description' => $category['description'],
                        'gender' => $category['gender'],
                        'min_age' => $category['min_age'],
                        'max_age' => $category['max_age'],
                        'active' => true,
                    ]
                );
            }
        }

        $this->command->info('✅ Categorias criadas!');
    }
}
