<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Shopmade\Shop\Enums\OrderStatus;
use Shopmade\Shop\Enums\ProductType;
use Shopmade\Shop\Models\Order;
use Shopmade\Shop\Models\Product;
use Tests\TestCase;

pest()->extend(TestCase::class)->use(RefreshDatabase::class);

function stripeKeys(): void
{
    config(['shop.stripe.key' => 'pk_test', 'shop.stripe.secret' => 'sk_test', 'shop.stripe.webhook_secret' => null]);
}

function payableOrder(array $attributes = []): Order
{
    $order = Order::factory()->create(['status' => OrderStatus::New, 'total' => 40, ...$attributes]);
    $order->items()->create(['name' => 'Filter', 'unit_price' => 40, 'qty' => 1, 'line_total' => 40]);

    return $order;
}

it('refuses the payment route without the order token and never leaks it', function () {
    $order = payableOrder();

    $response = $this->post(route('orders.stripe.store', ['order' => $order, 'token' => 'wrong']));

    $response->assertForbidden();
    expect((string) $response->headers->get('Location'))->not->toContain($order->access_token);
});

it('reuses an open Checkout session and sends an idempotency key', function () {
    stripeKeys();
    Http::fake([
        'api.stripe.com/v1/checkout/sessions/cs_open' => Http::response(['id' => 'cs_open', 'status' => 'open', 'url' => 'https://checkout.stripe.com/c/cs_open']),
        'api.stripe.com/v1/checkout/sessions' => Http::response(['id' => 'cs_open', 'url' => 'https://checkout.stripe.com/c/cs_open']),
    ]);
    $order = payableOrder();
    $url = route('orders.stripe.store', ['order' => $order, 'token' => $order->access_token]);

    $this->post($url)->assertRedirect('https://checkout.stripe.com/c/cs_open');
    $this->post($url)->assertRedirect('https://checkout.stripe.com/c/cs_open');

    Http::assertSentCount(2);
    Http::assertSent(fn (Request $request) => $request->method() === 'POST' && $request->hasHeader('Idempotency-Key')
        && str_contains($request['success_url'], route('shop.stripe.return'))
        && ! str_contains($request['success_url'], $order->access_token));
});

it('does not start a payment for an order that is not new', function () {
    stripeKeys();
    Http::fake();
    $order = payableOrder(['status' => OrderStatus::Cancelled]);

    $this->post(route('orders.stripe.store', ['order' => $order, 'token' => $order->access_token]))
        ->assertRedirect($order->publicUrl());

    Http::assertNothingSent();
});

it('fulfils only the order named by the session on return', function () {
    stripeKeys();
    $order = payableOrder(['stripe_session_id' => 'cs_paid']);
    $other = payableOrder();
    Http::fake(['api.stripe.com/*' => Http::response([
        'id' => 'cs_paid', 'payment_status' => 'paid', 'amount_total' => 4000, 'currency' => 'usd',
        'client_reference_id' => (string) $other->id, 'metadata' => ['order_id' => (string) $other->id],
    ])]);

    $this->get(route('shop.stripe.return', ['session_id' => 'cs_paid']))->assertRedirect($order->publicUrl());

    expect($order->fresh()->status)->toBe(OrderStatus::New)
        ->and($other->fresh()->status)->toBe(OrderStatus::New);
});

it('lets staff mark only a new order paid, and puts stock back on cancel', function () {
    $admin = User::factory()->admin()->create();
    $product = Product::factory()->create(['type' => ProductType::Physical, 'stock' => 5, 'active' => true, 'price' => 10]);
    $order = Order::factory()->create(['status' => OrderStatus::New]);
    $order->items()->create(['product_id' => $product->id, 'name' => $product->name, 'unit_price' => 10, 'qty' => 2, 'line_total' => 20]);

    $this->actingAs($admin)->post(route('orders.mark-cancelled', $order));
    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled)->and($product->fresh()->stock)->toBe(7);

    $this->actingAs($admin)->post(route('orders.mark-paid', $order))->assertSessionHas('status', 'Only a new order can be marked paid.');
    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled);
});

it('refuses checkout when stock runs out and creates no order', function () {
    $product = Product::factory()->create(['type' => ProductType::Physical, 'stock' => 2, 'active' => true, 'price' => 10]);
    $this->post(route('shop.cart.store', $product), ['qty' => 2]);
    $product->update(['stock' => 1]);

    $this->post(route('shop.checkout.store'), [
        'guest_name' => 'Ana', 'guest_email' => 'ana@example.com', 'address' => '1 Main St',
    ])->assertSessionHasErrors('cart');

    expect(Order::query()->count())->toBe(0)->and($product->fresh()->stock)->toBe(1);
});
