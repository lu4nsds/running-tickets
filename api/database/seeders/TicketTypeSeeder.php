<?php

namespace Database\Seeders;

use App\Enums\Currency;
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

        $events = Event::all();

        foreach ($events as $event) {
            // Determinar preços baseado no tipo de evento
            $basePrice = 6000; // R$ 60,00 padrão
            
            if (str_contains($event->title, 'Maratona')) {
                $basePrice = 12000; // R$ 120,00
            } elseif (str_contains($event->title, '10K')) {
                $basePrice = 7500; // R$ 75,00
            } elseif (str_contains($event->title, 'Trail')) {
                $basePrice = 9500; // R$ 95,00
            }

            $tickets = [
                [
                    'name' => 'Kit Completo',
                    'description' => 'Inscrição com camisa, número e medalha',
                    'price_cents' => $basePrice + 3000,
                    'quota' => 300,
                    'attributes' => ['kit' => 'camisa + medalha + número'],
                ],
                [
                    'name' => 'Kit Básico',
                    'description' => 'Inscrição com número e medalha (sem camisa)',
                    'price_cents' => $basePrice,
                    'quota' => 200,
                    'attributes' => ['kit' => 'medalha + número'],
                ],
                [
                    'name' => 'VIP',
                    'description' => 'Inscrição VIP com benefícios exclusivos',
                    'price_cents' => $basePrice + 6000,
                    'quota' => 50,
                    'attributes' => ['kit' => 'camisa premium + medalha especial + mochila + brindes'],
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
                        'currency' => Currency::BRL->value,
                        'quota' => $ticket['quota'],
                        'start_sale' => now()->subDays(5),
                        'end_sale' => $event->date_start->subDays(3),
                        'attributes' => $ticket['attributes'],
                        'active' => true,
                    ]
                );
            }
        }

        $this->command->info('✅ Tipos de ingressos criados!');
    }
}
