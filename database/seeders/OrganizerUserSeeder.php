<?php

namespace Database\Seeders;

use App\Models\Organizer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class OrganizerUserSeeder extends Seeder
{
    public function run(): void
    {
        // Segurança: só roda em ambiente local
        if (!app()->environment('local')) {
            return;
        }

        // Pega o organizador criado pelo OrganizerSeeder
        $organizer = Organizer::where('email', 'organizador@dev.local')->first();
        
        if (!$organizer) {
            $this->command->warn('⚠️  Organizador não encontrado. Execute o OrganizerSeeder primeiro.');
            return;
        }

        // Cria usuário João como admin do organizador
        $joao = User::firstOrCreate(
            ['email' => 'joao.org@teste.com'],
            [
                'name' => 'João Organizador',
                'password' => Hash::make('senha123'),
            ]
        );

        // Atribuir role 'user' (role padrão)
        if (!$joao->roles()->exists()) {
            $joao->assignRole('user');
        }

        // Vincular ao organizador como admin (remove vínculos anteriores)
        $joao->organizers()->sync([]);
        $joao->organizers()->attach($organizer->id, ['role' => 'admin']);

        // Cria usuário Maria como staff do organizador
        $maria = User::firstOrCreate(
            ['email' => 'maria.staff@teste.com'],
            [
                'name' => 'Maria Staff',
                'password' => Hash::make('senha123'),
            ]
        );

        if (!$maria->roles()->exists()) {
            $maria->assignRole('user');
        }

        // Vincular ao organizador como staff (remove vínculos anteriores)
        $maria->organizers()->sync([]);
        $maria->organizers()->attach($organizer->id, ['role' => 'staff']);

        $this->command->info('✅ Usuários do organizador criados!');
        $this->command->info('');
        $this->command->info('👤 João Organizador (Admin)');
        $this->command->info('📧 Email: joao.org@teste.com');
        $this->command->info('🔑 Senha: senha123');
        $this->command->info('');
        $this->command->info('👤 Maria Staff (Staff)');
        $this->command->info('📧 Email: maria.staff@teste.com');
        $this->command->info('🔑 Senha: senha123');
    }
}
