<?php

namespace Shopmade\Shop\Services;

use Illuminate\Http\Client\HttpClientException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
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
     * The Checkout URL for a new order: an open session is reused, otherwise one is created with an idempotency key, so
     * two clicks get the same session. No row lock is held during the Stripe calls; the new session id is saved only if
     * the order is still new and nobody saved another one meanwhile. Null when the order can't be paid online.
     *
     * @throws HttpClientException
     */
    public function createSession(Order $order): ?string
    {
        $order = Order::query()->with('items')->findOrFail($order->id);

        if ($order->status !== OrderStatus::New) {
            return null;
        }

        $previousSessionId = $order->stripe_session_id;

        if (filled($previousSessionId)) {
            $existing = $this->session($previousSessionId);

            if (($existing['status'] ?? null) === 'open' && filled($existing['url'] ?? null)) {
                return $existing['url'];
            }

            if (($existing['status'] ?? null) === 'complete') {
                $this->fulfil($existing, $order);

                return null;
            }
        }

        $created = $this->stripe()
            ->withHeaders(['Idempotency-Key' => 'order-'.$order->id.'-'.sha1((string) $previousSessionId)])
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
                    "line_items[{$index}][price_data][unit_amount]" => $this->minorUnits($item->unit_price),
                    "line_items[{$index}][quantity]" => (int) $item->qty,
                ])->all(),
            ])
            ->throw();

        $sessionId = (string) $created->json('id');

        $saved = DB::transaction(function () use ($order, $previousSessionId, $sessionId): bool {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($locked->stripe_session_id === $sessionId) {
                return true;
            }

            if ($locked->status !== OrderStatus::New || $locked->stripe_session_id !== $previousSessionId) {
                return false;
            }

            $locked->forceFill(['stripe_session_id' => $sessionId])->save();

            return true;
        });

        if (! $saved) {
            $this->expire($sessionId);

            return null;
        }

        return $created->json('url');
    }

    /**
     * Closes the order's Checkout Session so the buyer can't pay after staff cancel. False when the buyer has
     * already paid: the payment is recorded and the order must not be cancelled.
     *
     * @throws HttpClientException
     */
    public function closeSession(Order $order): bool
    {
        if (! $this->isConfigured() || blank($order->stripe_session_id)) {
            return true;
        }

        $session = $this->session($order->stripe_session_id);

        if (($session['status'] ?? null) === 'open') {
            try {
                $this->expire($order->stripe_session_id, throw: true);

                return true;
            } catch (RequestException) {
                $session = $this->session($order->stripe_session_id);
            }
        }

        if (($session['status'] ?? null) === 'complete') {
            $this->fulfil($session, $order);

            return false;
        }

        if (($session['status'] ?? null) !== 'expired') {
            throw new RuntimeException('Stripe did not close the Checkout Session for order '.$order->number.'.');
        }

        return true;
    }

    /**
     * Looks the session up at Stripe and marks its order paid. With $expected, only that order may be fulfilled.
     *
     * @throws HttpClientException when Stripe can't be reached, so the webhook answers 500 and Stripe retries
     */
    public function handleCompletedSession(string $sessionId, ?Order $expected = null): bool
    {
        return $this->fulfil($this->session($sessionId), $expected);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws HttpClientException
     */
    private function session(string $sessionId): array
    {
        $session = $this->stripe()->get(self::Api.'/checkout/sessions/'.rawurlencode($sessionId))->throw()->json();

        return is_array($session) ? $session : [];
    }

    /**
     * @throws HttpClientException when $throw is set
     */
    private function expire(string $sessionId, bool $throw = false): void
    {
        $response = $this->stripe()->asForm()->post(self::Api.'/checkout/sessions/'.rawurlencode($sessionId).'/expire');

        if ($throw) {
            $response->throw();
        }
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

        $matches = (string) ($session['client_reference_id'] ?? '') === (string) $order->id
            && filled($session['id'] ?? null)
            && $session['id'] === $order->stripe_session_id
            && (int) ($session['amount_total'] ?? -1) === $this->minorUnits($order->total)
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

    /**
     * Stripe amounts are in the currency's smallest unit: cents for most, whole units for zero-decimal currencies.
     */
    private function minorUnits(float|int|string|null $amount): int
    {
        $zeroDecimal = ['bif', 'clp', 'djf', 'gnf', 'jpy', 'kmf', 'krw', 'mga', 'pyg', 'rwf', 'ugx', 'vnd', 'vuv', 'xaf', 'xof', 'xpf'];

        return (int) round((float) $amount * (in_array($this->currency(), $zeroDecimal, true) ? 1 : 100));
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
     * @throws HttpClientException
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
