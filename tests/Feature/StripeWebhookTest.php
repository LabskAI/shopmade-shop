<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Shopmade\Shop\Enums\OrderStatus;
use Shopmade\Shop\Models\Order;
use Tests\TestCase;

pest()->extend(TestCase::class)->use(RefreshDatabase::class);

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function paidSession(Order $order, array $overrides = []): array
{
    return [
        'id' => 'cs_test_1',
        'payment_status' => 'paid',
        'amount_total' => (int) round((float) $order->total * 100),
        'currency' => 'usd',
        'client_reference_id' => (string) $order->id,
        'metadata' => ['order_id' => (string) $order->id],
        ...$overrides,
    ];
}

function completedSession(Order $order, array $overrides = []): array
{
    return ['type' => 'checkout.session.completed', 'data' => ['object' => paidSession($order, $overrides)]];
}

function signedWebhook(array $event): array
{
    $payload = json_encode($event);
    $timestamp = time();
    $signature = hash_hmac('sha256', $timestamp.'.'.$payload, 'whsec_test');

    return [['CONTENT_TYPE' => 'application/json', 'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}"], $payload];
}

it('marks an order paid only for a correctly signed event when a webhook secret is set', function () {
    config(['shop.stripe.webhook_secret' => 'whsec_test']);
    $order = Order::factory()->create(['stripe_session_id' => 'cs_test_1', 'status' => OrderStatus::New]);
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
    $order = Order::factory()->create(['stripe_session_id' => 'cs_test_1', 'status' => OrderStatus::New]);

    Http::fake(['api.stripe.com/*' => Http::sequence()
        ->push(paidSession($order, ['payment_status' => 'unpaid']))
        ->push(paidSession($order))]);

    $this->postJson(route('stripe.webhook'), completedSession($order))->assertOk()->assertSee('Ignored');
    expect($order->fresh()->status)->toBe(OrderStatus::New);

    $this->postJson(route('stripe.webhook'), completedSession($order))->assertOk()->assertSee('OK');
    expect($order->fresh()->status)->toBe(OrderStatus::Paid);
});

it('refuses a paid session whose amount, currency or order does not match', function (array $overrides) {
    config(['shop.stripe.webhook_secret' => 'whsec_test']);
    $order = Order::factory()->create(['stripe_session_id' => 'cs_test_1', 'status' => OrderStatus::New, 'total' => 40]);

    [$server, $payload] = signedWebhook(completedSession($order, $overrides));

    $this->call('POST', route('stripe.webhook'), [], [], [], $server, $payload)->assertOk()->assertSee('Ignored');
    expect($order->fresh()->status)->toBe(OrderStatus::New);
})->with([
    'amount' => [['amount_total' => 100]],
    'currency' => [['currency' => 'eur']],
    'client reference' => [['client_reference_id' => '999']],
    'missing client reference' => [['client_reference_id' => null]],
    'another session' => [['id' => 'cs_other']],
]);

it('answers 500 when the Stripe lookup times out', function () {
    config(['shop.stripe.webhook_secret' => null, 'shop.stripe.secret' => 'sk_test', 'shop.stripe.key' => 'pk_test']);
    $order = Order::factory()->create(['stripe_session_id' => 'cs_test_1', 'status' => OrderStatus::New]);
    Http::fake(fn () => throw new ConnectionException('timed out'));

    $this->postJson(route('stripe.webhook'), completedSession($order))->assertStatus(500);
    expect($order->fresh()->status)->toBe(OrderStatus::New);
});

it('records payment on a fulfilled order without moving it back to paid', function () {
    config(['shop.stripe.webhook_secret' => 'whsec_test']);
    $order = Order::factory()->create(['stripe_session_id' => 'cs_test_1', 'status' => OrderStatus::Fulfilled, 'total' => 40, 'paid_at' => null]);

    [$server, $payload] = signedWebhook(completedSession($order));

    $this->call('POST', route('stripe.webhook'), [], [], [], $server, $payload)->assertOk()->assertSee('OK');
    expect($order->fresh()->status)->toBe(OrderStatus::Fulfilled)->and($order->fresh()->paid_at)->not->toBeNull();
});

it('does not move a cancelled order to paid', function () {
    config(['shop.stripe.webhook_secret' => 'whsec_test']);
    $order = Order::factory()->create(['stripe_session_id' => 'cs_test_1', 'status' => OrderStatus::Cancelled, 'total' => 40]);

    [$server, $payload] = signedWebhook(completedSession($order));

    $this->call('POST', route('stripe.webhook'), [], [], [], $server, $payload)->assertOk()->assertSee('Ignored');
    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled);
});

it('answers 500 when the Stripe lookup fails so Stripe retries', function () {
    config(['shop.stripe.webhook_secret' => null, 'shop.stripe.secret' => 'sk_test', 'shop.stripe.key' => 'pk_test']);
    $order = Order::factory()->create(['stripe_session_id' => 'cs_test_1', 'status' => OrderStatus::New]);
    Http::fake(['api.stripe.com/*' => Http::response([], 503)]);

    $this->postJson(route('stripe.webhook'), completedSession($order))->assertStatus(500);
    expect($order->fresh()->status)->toBe(OrderStatus::New);
});

it('marks paid on async_payment_succeeded', function () {
    config(['shop.stripe.webhook_secret' => 'whsec_test']);
    $order = Order::factory()->create(['stripe_session_id' => 'cs_test_1', 'status' => OrderStatus::New, 'total' => 40]);

    [$server, $payload] = signedWebhook(['type' => 'checkout.session.async_payment_succeeded', 'data' => ['object' => paidSession($order)]]);

    $this->call('POST', route('stripe.webhook'), [], [], [], $server, $payload)->assertOk()->assertSee('OK');
    expect($order->fresh()->status)->toBe(OrderStatus::Paid);
});
