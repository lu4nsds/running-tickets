<?php

namespace Database\Factories;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        return [
            'code' => (string) Str::uuid(),
            'qr_path' => null,
            'status' => TicketStatus::ACTIVE,
            'issued_at' => now(),
        ];
    }
}
