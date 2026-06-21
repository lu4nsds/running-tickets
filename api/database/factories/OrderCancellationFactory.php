<?php

namespace Database\Factories;

use App\Enums\OrderCancellationStatus;
use App\Models\Order;
use App\Models\OrderCancellation;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderCancellationFactory extends Factory
{
    protected $model = OrderCancellation::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'requested_by' => null,
            'reason' => fake()->sentence(),
            'status' => OrderCancellationStatus::PENDING,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'refund_id' => null,
            'review_notes' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => OrderCancellationStatus::APPROVED,
            'reviewed_at' => now(),
            'refund_id' => (string) fake()->randomNumber(8),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => OrderCancellationStatus::REJECTED,
            'reviewed_at' => now(),
            'review_notes' => fake()->sentence(),
        ]);
    }
}
