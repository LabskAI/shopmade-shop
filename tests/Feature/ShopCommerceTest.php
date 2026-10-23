<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Shopmade\Shop\Enums\OrderStatus;
use Shopmade\Shop\Enums\ProductType;
use Shopmade\Shop\Models\Order;
use Shopmade\Shop\Models\Product;
use Shopmade\Shop\Services\StripeCheckout;
use Tests\TestCase;

pest()->extend(TestCase::class)->use(RefreshDatabase::class);

it('completes cart checkout and allows public token access', function () {
    $product = Product::factory()->create([
        'name' => 'Demo Shirt',
        'slug' => 'demo-shirt',
        'price' => 25.00,
        'stock' => 10,
        'type' => ProductType::Physical,
    ]);

    $this->post(route('shop.cart.store', $product), ['qty' => 2])
        ->assertRedirect(route('shop.cart'));

    $this->get(route('shop.checkout'))
        ->assertOk()
        ->assertSee('Demo Shirt');

    $this->post(route('shop.checkout.store'), [
        'guest_name' => 'Alex Buyer',
        'guest_email' => 'alex@example.com',
        'guest_phone' => '555-0100',
        'address' => '123 Main St, Oakland, CA',
        'notes' => 'Leave at door',
    ])->assertRedirect();

    $order = Order::query()->first();

    expect($order)->not->toBeNull()
        ->and($order->guest_email)->toBe('alex@example.com')
        ->and($order->status)->toBe(OrderStatus::New)
        ->and((float) $order->total)->toBe(50.0)
        ->and($order->items)->toHaveCount(1)
        ->and($product->fresh()->stock)->toBe(8);

    $this->get(route('orders.public.show', [
        'order' => $order->id,
        'token' => $order->access_token,
    ]))
        ->assertOk()
        ->assertSee($order->number)
        ->assertSee('Demo Shirt')
        ->assertSee('Alex Buyer');
});

it('denies public order access with a wrong token', function () {
    $order = Order::factory()->create();

    $this->get(route('orders.public.show', [
        'order' => $order->id,
        'token' => 'wrong-token',
    ]))->assertForbidden();
});

it('lets staff mark an order paid', function () {
    $builder = User::factory()->admin()->create();
    $order = Order::factory()->create([
        'status' => OrderStatus::New,
    ]);

    $this->actingAs($builder)
        ->post(route('orders.mark-paid', $order))
        ->assertRedirect(route('orders.show', $order));

    expect($order->fresh()->status)->toBe(OrderStatus::Paid)
        ->and($order->fresh()->paid_at)->not->toBeNull();
});

it('skips stripe checkout when keys are missing', function () {
    config([
        'shop.stripe.key' => null,
        'shop.stripe.secret' => null,
    ]);

    $order = Order::factory()->create();

    expect(app(StripeCheckout::class)->isConfigured())->toBeFalse();

    $this->post(route('orders.stripe.store', ['order' => $order, 'token' => $order->access_token]))
        ->assertRedirect($order->publicUrl())
        ->assertSessionHas('status');

    expect($order->fresh()->status)->toBe(OrderStatus::New);
});

it('redirects to stripe when keys are configured', function () {
    config([
        'shop.stripe.key' => 'pk_test_example',
        'shop.stripe.secret' => 'sk_test_example',
    ]);

    Http::fake([
        'api.stripe.com/*' => Http::response([
            'id' => 'cs_test_123',
            'url' => 'https://checkout.stripe.com/pay/cs_test_123',
        ], 200),
    ]);

    $order = Order::factory()->create([
        'total' => 40,
    ]);
    $order->items()->create([
        'name' => 'Filter',
        'unit_price' => 40,
        'qty' => 1,
        'line_total' => 40,
    ]);

    $this->post(route('orders.stripe.store', ['order' => $order, 'token' => $order->access_token]))
        ->assertRedirect('https://checkout.stripe.com/pay/cs_test_123');

    expect($order->fresh()->stripe_session_id)->toBe('cs_test_123');
});

it('redirects guests away from staff product management', function () {
    $this->get(route('products.create'))->assertRedirect(route('login'));
});

it('lets a user view orders but not manage products', function () {
    $user = User::factory()->user()->create();
    $order = Order::factory()->create();

    $this->actingAs($user)
        ->get(route('orders.index'))
        ->assertOk()
        ->assertSee($order->number);

    $this->actingAs($user)
        ->get(route('products.create'))
        ->assertForbidden();
});
