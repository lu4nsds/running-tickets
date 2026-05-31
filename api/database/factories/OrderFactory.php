<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Event;
use App\Models\Order;
use App\Models\Organizer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'organizer_id' => Organizer::factory(),
            'reference' => 'ORD-' . now()->year . '-' . strtoupper(Str::random(8)),
            'user_id' => null,
            'total_cents' => 5000,
            'currency' => 'BRL',
            'status' => OrderStatus::PENDING,
            'buyer_email' => fake()->safeEmail(),
            'buyer_phone' => fake()->numerify('###########'),
            'reserved_until' => now()->addMinutes(15),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => OrderStatus::PENDING,
            'reserved_until' => now()->addMinutes(15),
        ]);
    }

    public function processing(): static
    {
        return $this->state(fn () => [
            'status' => OrderStatus::PROCESSING,
            'reserved_until' => now()->addMinutes(15),
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status' => OrderStatus::PAID,
            'reserved_until' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => OrderStatus::FAILED,
            'reserved_until' => now()->addMinutes(15),
        ]);
    }

    public function reservationExpired(): static
    {
        return $this->state(fn () => [
            'reserved_until' => now()->subMinute(),
        ]);
    }
}
