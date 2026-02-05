<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@runningtickets.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password123'),
            ]
        );

        $superAdmin->assignRole('super_admin');

        $this->command->info('✅ Super Admin criado!');
        $this->command->info('📧 Email: admin@runningtickets.com');
        $this->command->info('🔑 Senha: password123');
    }
}
