<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            OrganizerSeeder::class,
            EventSeeder::class,
            CategorySeeder::class,
            TicketTypeSeeder::class,
        ]);
    }
}
