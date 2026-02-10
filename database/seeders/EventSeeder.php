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
                'status' => EventStatus::INATIVO->value, // Será ativado após config platform
                'meta' => ['distance' => '5km', 'kit' => 'camisa + medalha'],
                'payout_mode' => 'platform', // Pagamento pela plataforma
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
                'status' => EventStatus::INATIVO->value, // Sem credenciais ainda
                'meta' => ['distance' => '21km', 'kit' => 'camisa + medalha + hidratação'],
                'payout_mode' => 'direct', // Direct sem credenciais (organizador precisa configurar)
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
                'status' => EventStatus::INATIVO->value, // Será ativado após config platform
                'meta' => ['distance' => '10km', 'kit' => 'camisa + medalha'],
                'payout_mode' => 'platform', // Pagamento pela plataforma
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
                'status' => EventStatus::INATIVO->value, // Sem credenciais ainda
                'meta' => ['distance' => '15km', 'kit' => 'camisa + medalha + mochila'],
                'payout_mode' => 'direct', // Direct sem credenciais (organizador precisa configurar)
            ],
        ];

        foreach ($events as $eventData) {
            // Extrair payout_mode e credenciais antes de criar o evento
            $payoutMode = $eventData['payout_mode'] ?? null;
            $mpCredentials = $eventData['mp_credentials'] ?? null;
            unset($eventData['payout_mode'], $eventData['mp_credentials']);

            // Criar ou recuperar evento
            $event = Event::firstOrCreate(
                ['slug' => $eventData['slug']],
                array_merge(['organizer_id' => $organizer->id], $eventData)
            );

            // Criar configuração de pagamento se não existir
            if ($payoutMode && !$event->payoutSetting) {
                $this->createPayoutSetting($event, $payoutMode, $mpCredentials);
            }
        }

        $this->command->info('✅ Eventos criados com configurações de pagamento!');
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
