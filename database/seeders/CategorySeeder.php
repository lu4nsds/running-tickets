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
            ['name' => 'Masculino Geral', 'gender' => 'M', 'min_age' => null, 'max_age' => null],
            ['name' => 'Feminino Geral', 'gender' => 'F', 'min_age' => null, 'max_age' => null],
            ['name' => 'Masculino 18-29', 'gender' => 'M', 'min_age' => 18, 'max_age' => 29],
            ['name' => 'Feminino 18-29', 'gender' => 'F', 'min_age' => 18, 'max_age' => 29],
            ['name' => 'Masculino 30-39', 'gender' => 'M', 'min_age' => 30, 'max_age' => 39],
            ['name' => 'Feminino 30-39', 'gender' => 'F', 'min_age' => 30, 'max_age' => 39],
        ];

        foreach ($events as $event) {
            foreach ($categoryTemplates as $category) {
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

        $this->command->info('✅ Categorias criadas!');
    }
}
