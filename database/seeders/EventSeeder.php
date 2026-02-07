<?php

namespace Database\Seeders;

use App\Enums\EventStatus;
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

        $events = [
            [
                'slug' => 'corrida-dev-5k',
                'title' => 'Corrida Dev 5K',
                'description' => 'Evento de corrida para ambiente de desenvolvimento',
                'city' => 'Natal',
                'venue' => 'Parque das Dunas',
                'date_start' => now()->addDays(30),
                'date_end' => now()->addDays(30)->addHours(3),
                'max_participants' => 500,
                'status' => EventStatus::ATIVO->value,
                'meta' => ['distance' => '5km', 'kit' => 'camisa + medalha'],
            ],
            [
                'slug' => 'meia-maratona-litoral',
                'title' => 'Meia Maratona do Litoral',
                'description' => 'Meia maratona pela orla de Natal',
                'city' => 'Natal',
                'venue' => 'Via Costeira',
                'date_start' => now()->addDays(45),
                'date_end' => now()->addDays(45)->addHours(4),
                'max_participants' => 1000,
                'status' => EventStatus::INATIVO->value,
                'meta' => ['distance' => '21km', 'kit' => 'camisa + medalha + hidratação'],
            ],
            [
                'slug' => 'corrida-das-dunas-10k',
                'title' => 'Corrida das Dunas 10K',
                'description' => 'Corrida de 10km pelas dunas de Genipabu',
                'city' => 'Natal',
                'venue' => 'Genipabu',
                'date_start' => now()->addDays(60),
                'date_end' => now()->addDays(60)->addHours(3),
                'max_participants' => 700,
                'status' => EventStatus::ATIVO->value,
                'meta' => ['distance' => '10km', 'kit' => 'camisa + medalha'],
            ],
            [
                'slug' => 'trail-run-montanhas',
                'title' => 'Trail Run Montanhas RN',
                'description' => 'Trail running pelas serras do RN',
                'city' => 'Martins',
                'venue' => 'Serra de Martins',
                'date_start' => now()->addDays(75),
                'date_end' => now()->addDays(75)->addHours(5),
                'max_participants' => 300,
                'status' => EventStatus::ATIVO->value,
                'meta' => ['distance' => '15km', 'kit' => 'camisa + medalha + mochila'],
            ],
        ];

        foreach ($events as $eventData) {
            Event::firstOrCreate(
                ['slug' => $eventData['slug']],
                array_merge(['organizer_id' => $organizer->id], $eventData)
            );
        }

        $this->command->info('✅ Eventos criados!');
    }
}
