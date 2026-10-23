<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Shopmade\Shop\Enums\OrderStatus;
use Shopmade\Shop\Models\Order;
use Tests\TestCase;

pest()->extend(TestCase::class)->use(RefreshDatabase::class);

function completedSession(Order $order): array
{
    return [
        'type' => 'checkout.session.completed',
        'data' => ['object' => ['id' => 'cs_test_1', 'payment_status' => 'paid', 'metadata' => ['order_id' => $order->id]]],
    ];
}

it('marks an order paid only for a correctly signed event when a webhook secret is set', function () {
    config(['shop.stripe.webhook_secret' => 'whsec_test']);
    $order = Order::factory()->create(['status' => OrderStatus::New]);
    $payload = json_encode(completedSession($order));

    $this->call('POST', route('stripe.webhook'), [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_STRIPE_SIGNATURE' => 't='.time().',v1=forged'], $payload)
        ->assertStatus(400);
    expect($order->fresh()->status)->toBe(OrderStatus::New);

    $timestamp = time();
    $signature = hash_hmac('sha256', $timestamp.'.'.$payload, 'whsec_test');

    $this->call('POST', route('stripe.webhook'), [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}"], $payload)
        ->assertOk()
        ->assertSee('OK');
    expect($order->fresh()->status)->toBe(OrderStatus::Paid);
});

it('confirms an unsigned event with Stripe instead of trusting the payload', function () {
    config(['shop.stripe.webhook_secret' => null, 'shop.stripe.secret' => 'sk_test', 'shop.stripe.key' => 'pk_test']);
    $order = Order::factory()->create(['status' => OrderStatus::New]);

    Http::fake(['api.stripe.com/*' => Http::sequence()
        ->push(['id' => 'cs_test_1', 'payment_status' => 'unpaid', 'metadata' => ['order_id' => $order->id]])
        ->push(['id' => 'cs_test_1', 'payment_status' => 'paid', 'metadata' => ['order_id' => $order->id]])]);

    $this->postJson(route('stripe.webhook'), completedSession($order))->assertOk()->assertSee('Ignored');
    expect($order->fresh()->status)->toBe(OrderStatus::New);

    $this->postJson(route('stripe.webhook'), completedSession($order))->assertOk()->assertSee('OK');
    expect($order->fresh()->status)->toBe(OrderStatus::Paid);
});
