<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Shopmade\Shop\Enums\OrderStatus;
use Shopmade\Shop\Models\Order;
use Shopmade\Shop\Models\Product;
use Tests\TestCase;

pest()->extend(TestCase::class)->use(RefreshDatabase::class);

it('shows order counts on the dashboard', function () {
    Order::factory()->create(['status' => OrderStatus::New]);
    Order::factory()->create(['status' => OrderStatus::New]);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSeeInOrder(['New orders', '2'])
        ->assertSee(route('products.index'), false);
});

it('lists live products over MCP', function () {
    require_once base_path('tests/Feature/Agents/Mcp/McpHelpers.php');

    Product::factory()->create(['name' => 'Gift card', 'active' => true]);
    Product::factory()->create(['name' => 'Old shirt', 'active' => false]);

    [$bearer, $session] = mcpDirector(['operator']);

    mcpCall('products_list', ['active_only' => true], $bearer, $session)
        ->assertOk()
        ->assertJsonCount(1, 'result.structuredContent.products')
        ->assertJsonPath('result.structuredContent.products.0.name', 'Gift card');
});
