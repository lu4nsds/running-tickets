<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\AdminUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $admins = [
            ['name' => 'Luan Souza', 'email' => 'luan.souza@runningtickets.com.br'],
            ['name' => 'Pedro Aguiar', 'email' => 'pedro.aguiar@runningtickets.com.br'],
        ];

        foreach ($admins as $admin) {
            $user = AdminUser::firstOrCreate(
                ['email' => $admin['email']],
                [
                    'name' => $admin['name'],
                    'password' => Hash::make(Str::password(32)),
                ]
            );

            $user->assignRole(UserRole::SUPER_ADMIN->value);

            $status = $user->wasRecentlyCreated ? 'criado' : 'já existia';
            $this->command->info("Super Admin {$admin['email']}: {$status}.");
        }

        $this->command->info('Peça a cada admin para definir a senha via "Esqueci minha senha" no /admin.');
    }
}
