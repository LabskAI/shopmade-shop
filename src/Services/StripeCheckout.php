<?php

namespace Shopmade\Shop\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Shopmade\Shop\Enums\OrderStatus;
use Shopmade\Shop\Models\Order;

class StripeCheckout
{
    private const string Api = 'https://api.stripe.com/v1';

    public function isConfigured(): bool
    {
        return filled(config('shop.stripe.secret')) && filled(config('shop.stripe.key'));
    }

    /**
     * The Checkout URL for a new order: an open session is reused, otherwise one is created with an idempotency key,
     * all under a row lock so two clicks can't open two payments. Null when the order is no longer new.
     *
     * @throws RequestException
     */
    public function createSession(Order $order): ?string
    {
        return DB::transaction(function () use ($order): ?string {
            $order = Order::query()->lockForUpdate()->with('items')->findOrFail($order->id);

            if ($order->status !== OrderStatus::New) {
                return null;
            }

            if (filled($order->stripe_session_id)) {
                $existing = $this->stripe()->get(self::Api.'/checkout/sessions/'.rawurlencode($order->stripe_session_id))->throw();

                if ($existing->json('status') === 'open' && filled($existing->json('url'))) {
                    return $existing->json('url');
                }

                if ($existing->json('status') === 'complete') {
                    return null;
                }
            }

            $response = $this->stripe()
                ->withHeaders(['Idempotency-Key' => 'order-'.$order->id.'-'.sha1((string) $order->stripe_session_id)])
                ->asForm()
                ->post(self::Api.'/checkout/sessions', [
                    'mode' => 'payment',
                    'payment_method_types' => ['card'],
                    'success_url' => route('shop.stripe.return').'?session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url' => route('shop.checkout'),
                    'customer_email' => $order->guest_email,
                    'client_reference_id' => (string) $order->id,
                    'metadata' => [
                        'order_id' => (string) $order->id,
                        'order_number' => $order->number,
                    ],
                    ...$order->items->values()->flatMap(fn ($item, int $index) => [
                        "line_items[{$index}][price_data][currency]" => $this->currency(),
                        "line_items[{$index}][price_data][product_data][name]" => $item->name,
                        "line_items[{$index}][price_data][unit_amount]" => self::cents($item->unit_price),
                        "line_items[{$index}][quantity]" => (int) $item->qty,
                    ])->all(),
                ])
                ->throw();

            $order->forceFill(['stripe_session_id' => $response->json('id')])->save();

            return $response->json('url');
        });
    }

    /**
     * Looks the session up at Stripe and marks its order paid. With $expected, only that order may be fulfilled.
     *
     * @throws RequestException when Stripe can't be reached, so the webhook answers 500 and Stripe retries
     */
    public function handleCompletedSession(string $sessionId, ?Order $expected = null): bool
    {
        $session = $this->stripe()->get(self::Api.'/checkout/sessions/'.rawurlencode($sessionId))->throw()->json();

        return is_array($session) && $this->fulfil($session, $expected);
    }

    /**
     * The order a paid session belongs to, after checking its order id, amount and currency against the order.
     *
     * @param  array<string, mixed>  $session
     */
    private function fulfil(array $session, ?Order $expected = null): bool
    {
        if (($session['payment_status'] ?? null) !== 'paid') {
            return false;
        }

        $order = Order::query()->find($session['metadata']['order_id'] ?? null);

        if ($order === null || ($expected !== null && ! $order->is($expected))) {
            return false;
        }

        $matches = (string) ($session['client_reference_id'] ?? $order->id) === (string) $order->id
            && (int) ($session['amount_total'] ?? -1) === self::cents($order->total)
            && strtolower((string) ($session['currency'] ?? '')) === $this->currency();

        if (! $matches) {
            Log::error('Stripe session does not match its order; not marking it paid.', [
                'order' => $order->number,
                'session_id' => $session['id'] ?? null,
                'amount_total' => $session['amount_total'] ?? null,
                'currency' => $session['currency'] ?? null,
            ]);

            return false;
        }

        return $order->markPaid($session['id'] ?? null);
    }

    private function stripe(): PendingRequest
    {
        return Http::withToken((string) config('shop.stripe.secret'))->acceptJson()->connectTimeout(5)->timeout(20);
    }

    private function currency(): string
    {
        return strtolower((string) config('shop.stripe.currency', 'usd'));
    }

    private static function cents(float|int|string|null $amount): int
    {
        return (int) round((float) $amount * 100);
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
     * Marks the order paid for a checkout.session.completed or async_payment_succeeded event. With a webhook secret
     * the caller has already checked the signature; without one the payload is not trusted and the session is
     * confirmed with Stripe.
     *
     * @param  array<string, mixed>  $payload
     *
     * @throws RequestException
     */
    public function handleWebhookPayload(array $payload, bool $signed): bool
    {
        if (! in_array($payload['type'] ?? null, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true)) {
            return false;
        }

        $session = $payload['data']['object'] ?? [];

        if (! is_array($session) || ! is_string($session['id'] ?? null)) {
            return false;
        }

        return $signed ? $this->fulfil($session) : $this->handleCompletedSession($session['id']);
    }
}
