<?php

namespace Database\Seeders;

use App\Enums\OrganizerRole;
use App\Models\AdminUser;
use App\Models\Organizer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class OrganizerUserSeeder extends Seeder
{
    public function run(): void
    {
        $organizer = Organizer::where('email', 'organizador@dev.local')->first();

        if (! $organizer) {
            $this->command->warn('⚠️  Organizador não encontrado. Execute o OrganizerSeeder primeiro.');

            return;
        }

        // Cria usuário João como admin do organizador
        $joao = AdminUser::firstOrCreate(
            ['email' => 'joao.org@teste.com'],
            [
                'name' => 'João Organizador',
                'password' => Hash::make('senha123'),
            ]
        );

        // Vincular ao organizador como admin (remove vínculos anteriores)
        $joao->organizers()->sync([]);
        $joao->organizers()->attach($organizer->id, ['role' => OrganizerRole::ADMIN->value]);

        // Cria usuário Maria como staff do organizador
        $maria = AdminUser::firstOrCreate(
            ['email' => 'maria.staff@teste.com'],
            [
                'name' => 'Maria Staff',
                'password' => Hash::make('senha123'),
            ]
        );

        // Vincular ao organizador como staff (remove vínculos anteriores)
        $maria->organizers()->sync([]);
        $maria->organizers()->attach($organizer->id, ['role' => OrganizerRole::STAFF->value]);

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
