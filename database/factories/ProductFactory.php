<?php

namespace Shopmade\Shop\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Shopmade\Shop\Enums\ProductType;
use Shopmade\Shop\Models\Product;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = fake()->words(3, true);

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(100, 999),
            'description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 10, 500),
            'sku' => fake()->optional()->bothify('SKU-####'),
            'image_url' => null,
            'type' => ProductType::Physical,
            'stock' => fake()->numberBetween(5, 50),
            'active' => true,
        ];
    }

    public function servicePackage(): static
    {
        return $this->state(fn () => [
            'type' => ProductType::ServicePackage,
            'stock' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['active' => false]);
    }
}
