<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Organizer;

class OrganizerSeeder extends Seeder
{
    public function run(): void
    {
        // Segurança: só roda em ambiente local
        if (!app()->environment('local')) {
            return;
        }

        $organizers = [
            [
                'email' => 'organizador@dev.local',
                'name' => 'Organizador Dev',
                'document' => '12345678000199',
                'phone' => '84999999999',
                'status' => 'active',
            ],
            [
                'email' => 'esportes.rn@example.com',
                'name' => 'Esportes RN',
                'document' => '98765432000188',
                'phone' => '84988887777',
                'status' => 'active',
            ],
            [
                'email' => 'corridas.nordeste@example.com',
                'name' => 'Corridas do Nordeste',
                'document' => '11223344000155',
                'phone' => '84977776666',
                'status' => 'active',
            ],
            [
                'email' => 'running.club@example.com',
                'name' => 'Running Club Brasil',
                'document' => '55667788000122',
                'phone' => '84966665555',
                'status' => 'active',
            ],
            [
                'email' => 'eventos.esportivos@example.com',
                'name' => 'Eventos Esportivos Pro',
                'document' => '99887766000133',
                'phone' => '84955554444',
                'status' => 'active',
            ],
            [
                'email' => 'maratona.brasil@example.com',
                'name' => 'Maratona Brasil',
                'document' => '44332211000177',
                'phone' => '84944443333',
                'status' => 'active',
            ],
            [
                'email' => 'trail.adventures@example.com',
                'name' => 'Trail Adventures',
                'document' => '77665544000166',
                'phone' => '84933332222',
                'status' => 'active',
            ],
            [
                'email' => 'run.fast@example.com',
                'name' => 'Run Fast Eventos',
                'document' => '22334455000144',
                'phone' => '84922221111',
                'status' => 'active',
            ],
        ];

        foreach ($organizers as $organizerData) {
            Organizer::firstOrCreate(
                ['email' => $organizerData['email']],
                $organizerData
            );
        }

        $this->command->info('✅ ' . count($organizers) . ' organizadores criados!');
    }
}
