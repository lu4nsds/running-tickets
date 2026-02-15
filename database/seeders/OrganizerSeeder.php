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
                'address' => 'Av. Senador Salgado Filho, 1500',
                'address_complement' => 'Sala 302',
                'neighborhood' => 'Lagoa Nova',
                'city' => 'Natal',
                'state' => 'RN',
                'zip_code' => '59056-000',
                'status' => 'active',
            ],
            [
                'email' => 'esportes.rn@example.com',
                'name' => 'Esportes RN',
                'document' => '98765432000188',
                'phone' => '84988887777',
                'address' => 'Rua Dr. José Gonçalves, 230',
                'address_complement' => null,
                'neighborhood' => 'Petrópolis',
                'city' => 'Natal',
                'state' => 'RN',
                'zip_code' => '59012-090',
                'status' => 'active',
            ],
            [
                'email' => 'corridas.nordeste@example.com',
                'name' => 'Corridas do Nordeste',
                'document' => '11223344000155',
                'phone' => '84977776666',
                'address' => 'Av. Hermes da Fonseca, 800',
                'address_complement' => 'Andar 5',
                'neighborhood' => 'Tirol',
                'city' => 'Natal',
                'state' => 'RN',
                'zip_code' => '59015-001',
                'status' => 'active',
            ],
            [
                'email' => 'running.club@example.com',
                'name' => 'Running Club Brasil',
                'document' => '55667788000122',
                'phone' => '84966665555',
                'address' => 'Av. Paulista, 1000',
                'address_complement' => 'Conj. 1205',
                'neighborhood' => 'Bela Vista',
                'city' => 'São Paulo',
                'state' => 'SP',
                'zip_code' => '01310-100',
                'status' => 'active',
            ],
            [
                'email' => 'eventos.esportivos@example.com',
                'name' => 'Eventos Esportivos Pro',
                'document' => '99887766000133',
                'phone' => '84955554444',
                'address' => 'Rua da Assembleia, 100',
                'address_complement' => 'Loja 12',
                'neighborhood' => 'Centro',
                'city' => 'Rio de Janeiro',
                'state' => 'RJ',
                'zip_code' => '20011-000',
                'status' => 'active',
            ],
            [
                'email' => 'maratona.brasil@example.com',
                'name' => 'Maratona Brasil',
                'document' => '44332211000177',
                'phone' => '84944443333',
                'address' => 'Rua XV de Novembro, 500',
                'address_complement' => null,
                'neighborhood' => 'Centro',
                'city' => 'Curitiba',
                'state' => 'PR',
                'zip_code' => '80020-310',
                'status' => 'active',
            ],
            [
                'email' => 'trail.adventures@example.com',
                'name' => 'Trail Adventures',
                'document' => '77665544000166',
                'phone' => '84933332222',
                'address' => 'Av. Beira Mar, 3000',
                'address_complement' => 'Bloco B, Sala 45',
                'neighborhood' => 'Meireles',
                'city' => 'Fortaleza',
                'state' => 'CE',
                'zip_code' => '60165-121',
                'status' => 'active',
            ],
            [
                'email' => 'run.fast@example.com',
                'name' => 'Run Fast Eventos',
                'document' => '22334455000144',
                'phone' => '84922221111',
                'address' => 'Av. Boa Viagem, 2500',
                'address_complement' => null,
                'neighborhood' => 'Boa Viagem',
                'city' => 'Recife',
                'state' => 'PE',
                'zip_code' => '51020-001',
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
