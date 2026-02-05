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

        Organizer::firstOrCreate(
            ['email' => 'organizador@dev.local'],
            [
                'name' => 'Organizador Dev',
                'document' => '12345678000199',
                'phone' => '84999999999',
                'status' => 'active',
            ]
        );
    }
}
