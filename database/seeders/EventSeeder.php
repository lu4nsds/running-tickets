<?php

namespace Database\Seeders;

use App\Enums\EventStatus;
use Illuminate\Database\Seeder;
use App\Models\Event;
use App\Models\EventPayoutSetting;
use App\Models\Organizer;
use App\Services\MercadoPagoService;
use Illuminate\Support\Str;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        // Segurança: só roda em ambiente local
        if (!app()->environment('local')) {
            return;
        }

        $organizers = Organizer::all();

        if ($organizers->isEmpty()) {
            $this->command->error('❌ Nenhum organizador encontrado! Execute OrganizerSeeder primeiro.');
            return;
        }

        $eventTemplates = [
            [
                'suffix' => '5k',
                'title_template' => 'Corrida {city} 5K',
                'description' => 'Corrida de 5km pela cidade',
                'distance' => '5km',
                'duration_hours' => 3,
                'max_participants' => 500,
            ],
            [
                'suffix' => '10k',
                'title_template' => 'Corrida das Dunas {city} 10K',
                'description' => 'Corrida de 10km',
                'distance' => '10km',
                'duration_hours' => 4,
                'max_participants' => 700,
            ],
            [
                'suffix' => 'meia-maratona',
                'title_template' => 'Meia Maratona {city}',
                'description' => 'Meia maratona de 21km',
                'distance' => '21km',
                'duration_hours' => 5,
                'max_participants' => 1000,
            ],
            [
                'suffix' => 'trail',
                'title_template' => 'Trail Run {city}',
                'description' => 'Trail running',
                'distance' => '15km',
                'duration_hours' => 5,
                'max_participants' => 300,
            ],
        ];

        $cities = [
            ['name' => 'Natal', 'venues' => ['Parque das Dunas', 'Via Costeira', 'Ponta Negra']],
            ['name' => 'Mossoró', 'venues' => ['Centro', 'Abolição', 'Ilha de Santa Luzia']],
            ['name' => 'Parnamirim', 'venues' => ['Cotovelo', 'Pirangi', 'Centro']],
            ['name' => 'João Pessoa', 'venues' => ['Cabo Branco', 'Tambaú', 'Manaíra']],
            ['name' => 'Recife', 'venues' => ['Boa Viagem', 'Recife Antigo', 'Parque Dona Lindu']],
        ];

        $eventCount = 0;

        // Criar 3-4 eventos para cada organizador
        foreach ($organizers as $organizer) {
            $numEvents = rand(3, 4);
            
            for ($i = 0; $i < $numEvents; $i++) {
                $template = $eventTemplates[array_rand($eventTemplates)];
                $city = $cities[array_rand($cities)];
                $venue = $city['venues'][array_rand($city['venues'])];
                
                $daysInFuture = rand(15, 120);
                $payoutMode = rand(0, 10) < 7 ? 'platform' : 'direct'; // 70% platform, 30% direct
                
                $slug = Str::slug($template['title_template'] . ' ' . $city['name'] . ' ' . $daysInFuture);
                $title = str_replace('{city}', $city['name'], $template['title_template']);
                
                $event = Event::firstOrCreate(
                    ['slug' => $slug],
                    [
                        'organizer_id' => $organizer->id,
                        'title' => $title,
                        'description' => $template['description'],
                        'city' => $city['name'],
                        'venue' => $venue,
                        'date_start' => now()->addDays($daysInFuture),
                        'date_end' => now()->addDays($daysInFuture)->addHours($template['duration_hours']),
                        'max_participants' => $template['max_participants'],
                        'status' => EventStatus::INATIVO->value,
                        'meta' => [
                            'distance' => $template['distance'],
                            'kit' => 'camisa + medalha',
                        ],
                    ]
                );

                // Criar configuração de pagamento
                if (!$event->payoutSetting) {
                    $this->createPayoutSetting($event, $payoutMode, null);
                }
                
                $eventCount++;
            }
        }

        $this->command->info("✅ {$eventCount} eventos criados!");
    }

    /**
     * Criar configuração de pagamento para um evento
     */
    private function createPayoutSetting(Event $event, string $payoutMode, ?array $mpCredentials = null): void
    {
        if ($payoutMode === 'platform') {
            // Modo Platform: usar credenciais da plataforma
            try {
                $platformCredentials = MercadoPagoService::getPlatformCredentials();

                EventPayoutSetting::create([
                    'event_id' => $event->id,
                    'method' => 'mercadopago',
                    'payout_mode' => 'platform',
                    'provider' => 'Mercado Pago',
                    'details' => $platformCredentials,
                    'active' => true,
                ]);

                // Ativar evento automaticamente
                $event->update(['status' => EventStatus::ATIVO->value]);

                $this->command->info("  → {$event->title}: Platform (Ativo)");
            } catch (\Exception $e) {
                $this->command->warn("  ⚠️  {$event->title}: Falha ao configurar platform - {$e->getMessage()}");
            }
        } elseif ($payoutMode === 'direct') {
            // Modo Direct
            $details = $mpCredentials ?? [];

            EventPayoutSetting::create([
                'event_id' => $event->id,
                'method' => 'mercadopago',
                'payout_mode' => 'direct',
                'provider' => 'Mercado Pago',
                'details' => $details,
                'active' => true,
            ]);

            // Se tem credenciais, ativar evento
            if (!empty($details)) {
                $event->update(['status' => EventStatus::ATIVO->value]);
                $this->command->info("  → {$event->title}: Direct com credenciais (Ativo)");
            } else {
                $this->command->info("  → {$event->title}: Direct sem credenciais (Inativo)");
            }
        }
    }
}
