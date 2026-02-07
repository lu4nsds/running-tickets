<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Super Administrador',
                'slug' => UserRole::SUPER_ADMIN->value,
                'description' => 'Acesso total à plataforma. Gerencia todos os organizadores e eventos.',
            ],
            [
                'name' => 'Usuário',
                'slug' => UserRole::USER->value,
                'description' => 'Usuário comum do sistema. Pode comprar ingressos e gerenciar suas inscrições.',
            ],
        ];

        foreach ($roles as $roleData) {
            Role::firstOrCreate(
                ['slug' => $roleData['slug']],
                $roleData
            );
        }
    }
}
