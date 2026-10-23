<?php

namespace Shopmade\Shop\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Shopmade\Shop\Models\Order;
use Shopmade\Shop\Models\OrderItem;
use Shopmade\Shop\Models\Product;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        $qty = fake()->numberBetween(1, 3);
        $unitPrice = fake()->randomFloat(2, 10, 200);

        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'name' => fake()->words(3, true),
            'unit_price' => $unitPrice,
            'qty' => $qty,
            'line_total' => round($qty * $unitPrice, 2),
        ];
    }
}
