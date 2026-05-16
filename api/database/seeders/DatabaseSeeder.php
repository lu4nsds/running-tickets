<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            AdminSeeder::class,
        ]);

        if (app()->environment('production')) {
            return;
        }

        $this->call([
            OrganizerSeeder::class,
            OrganizerUserSeeder::class,
            EventSeeder::class,
            CategorySeeder::class,
            TicketTypeSeeder::class,
            OrderSeeder::class,
        ]);
    }
}
