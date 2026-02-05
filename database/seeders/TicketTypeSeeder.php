<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TicketType;
use App\Models\Event;

class TicketTypeSeeder extends Seeder
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

        $tickets = [
            [
                'name' => '5K - Kit Completo',
                'description' => 'Inscrição com camisa, número e medalha',
                'price_cents' => 8900, // R$ 89,00
                'quota' => 300,
                'attributes' => [
                    'kit' => 'camisa + medalha',
                ],
            ],
            [
                'name' => '5K - Sem Camisa',
                'description' => 'Inscrição sem camisa',
                'price_cents' => 5900, // R$ 59,00
                'quota' => 200,
                'attributes' => [
                    'kit' => 'medalha',
                ],
            ],
        ];

        foreach ($tickets as $ticket) {
            TicketType::firstOrCreate(
                [
                    'event_id' => $event->id,
                    'name' => $ticket['name'],
                ],
                [
                    'description' => $ticket['description'],
                    'price_cents' => $ticket['price_cents'],
                    'currency' => 'BRL',
                    'quota' => $ticket['quota'],
                    'start_sale' => now()->subDays(1),
                    'end_sale' => now()->addDays(20),
                    'attributes' => $ticket['attributes'],
                    'active' => true,
                ]
            );
        }
    }
}
