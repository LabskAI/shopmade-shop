<?php

namespace Shopmade\Shop\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Shopmade\Shop\Enums\OrderStatus;
use Shopmade\Shop\Models\Order;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'number' => Order::generateNumber(),
            'status' => OrderStatus::New,
            'customer_id' => null,
            'guest_name' => fake()->name(),
            'guest_email' => fake()->safeEmail(),
            'guest_phone' => fake()->optional()->phoneNumber(),
            'address' => fake()->streetAddress().', '.fake()->city().', '.fake()->stateAbbr(),
            'notes' => null,
            'total' => 0,
            'stripe_session_id' => null,
            'paid_at' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status' => OrderStatus::Paid,
            'paid_at' => now(),
        ]);
    }
}
