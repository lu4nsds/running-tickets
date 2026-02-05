<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Event;
use App\Models\Organizer;
use Illuminate\Support\Str;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        // Segurança: só roda em ambiente local
        if (!app()->environment('local')) {
            return;
        }

        $organizer = Organizer::where('email', 'organizador@dev.local')->first();

        if (!$organizer) {
            return;
        }

        Event::firstOrCreate(
            ['slug' => 'corrida-dev-5k'],
            [
                'organizer_id' => $organizer->id,
                'title' => 'Corrida Dev 5K',
                'description' => 'Evento de corrida para ambiente de desenvolvimento',
                'city' => 'Natal',
                'venue' => 'Parque das Dunas',
                'date_start' => now()->addDays(30),
                'date_end' => now()->addDays(30)->addHours(3),
                'max_participants' => 500,
                'banner_url' => null,
                'meta' => [
                    'distance' => '5km',
                    'kit' => 'camisa + medalha',
                ],
            ]
        );
    }
}
