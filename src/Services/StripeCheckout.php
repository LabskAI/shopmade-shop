<?php

namespace Shopmade\Shop\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Shopmade\Shop\Models\Order;

class StripeCheckout
{
    public function isConfigured(): bool
    {
        return filled(config('shop.stripe.secret')) && filled(config('shop.stripe.key'));
    }

    /**
     * @throws RequestException
     */
    public function createSession(Order $order): string
    {
        $order->load('items');

        $lineItems = $order->items->map(fn ($item) => [
            'price_data' => [
                'currency' => config('shop.stripe.currency', 'usd'),
                'product_data' => [
                    'name' => $item->name,
                ],
                'unit_amount' => (int) round((float) $item->unit_price * 100),
            ],
            'quantity' => (int) $item->qty,
        ])->values()->all();

        $response = Http::withToken(config('shop.stripe.secret'))
            ->asForm()
            ->post('https://api.stripe.com/v1/checkout/sessions', [
                'mode' => 'payment',
                'success_url' => $order->publicUrl().'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('shop.checkout'),
                'customer_email' => $order->guest_email,
                'client_reference_id' => (string) $order->id,
                'metadata' => [
                    'order_id' => (string) $order->id,
                    'order_number' => $order->number,
                ],
                ...collect($lineItems)->flatMap(fn (array $item, int $index) => [
                    "line_items[{$index}][price_data][currency]" => $item['price_data']['currency'],
                    "line_items[{$index}][price_data][product_data][name]" => $item['price_data']['product_data']['name'],
                    "line_items[{$index}][price_data][unit_amount]" => $item['price_data']['unit_amount'],
                    "line_items[{$index}][quantity]" => $item['quantity'],
                ])->all(),
            ])
            ->throw();

        $sessionId = $response->json('id');
        $checkoutUrl = $response->json('url');

        $order->update(['stripe_session_id' => $sessionId]);

        return $checkoutUrl;
    }

    public function handleCompletedSession(string $sessionId): bool
    {
        try {
            $response = Http::withToken(config('shop.stripe.secret'))
                ->get('https://api.stripe.com/v1/checkout/sessions/'.rawurlencode($sessionId))
                ->throw();

            if ($response->json('payment_status') !== 'paid') {
                return false;
            }

            $order = $this->resolveOrder($response->json());

            if (! $order) {
                return false;
            }

            $order->markPaid($sessionId);

            return true;
        } catch (RequestException $exception) {
            Log::warning('Stripe session lookup failed.', [
                'session_id' => $sessionId,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Checks the Stripe-Signature header (v1 HMAC of "timestamp.payload") against the webhook secret, within a
     * five minute tolerance.
     */
    public function hasValidSignature(string $payload, ?string $header, int $tolerance = 300): bool
    {
        $secret = (string) config('shop.stripe.webhook_secret');

        if ($secret === '' || $header === null) {
            return false;
        }

        $timestamp = null;
        $signatures = [];

        foreach (explode(',', $header) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, '');

            if ($key === 't') {
                $timestamp = (int) $value;
            } elseif ($key === 'v1') {
                $signatures[] = $value;
            }
        }

        if ($timestamp === null || abs(time() - $timestamp) > $tolerance) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Marks the order paid for a checkout.session.completed event. With a webhook secret the caller has already
     * checked the signature; without one the payload is not trusted and the session is confirmed with Stripe.
     *
     * @param  array<string, mixed>  $payload
     */
    public function handleWebhookPayload(array $payload, bool $signed): bool
    {
        if (($payload['type'] ?? null) !== 'checkout.session.completed') {
            return false;
        }

        $session = $payload['data']['object'] ?? [];

        if (! $signed) {
            return is_string($session['id'] ?? null) && $this->handleCompletedSession($session['id']);
        }

        if (($session['payment_status'] ?? null) !== 'paid') {
            return false;
        }

        $order = $this->resolveOrder($session);

        if (! $order) {
            return false;
        }

        $order->markPaid($session['id'] ?? null);

        return true;
    }

    /**
     * @param  array<string, mixed>  $session
     */
    private function resolveOrder(array $session): ?Order
    {
        $orderId = $session['metadata']['order_id'] ?? $session['client_reference_id'] ?? null;

        if ($orderId) {
            return Order::query()->find($orderId);
        }

        $sessionId = $session['id'] ?? null;

        if ($sessionId) {
            return Order::query()->where('stripe_session_id', $sessionId)->first();
        }

        return null;
    }
}
