<?php

namespace Shopmade\Shop\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Shopmade\Shop\Models\Order;
use Shopmade\Shop\Services\StripeCheckout;

class StripeCheckoutController extends Controller
{
    /**
     * Starts (or resumes) Stripe Checkout for the order behind a secret order link.
     */
    public function store(Order $order, string $token, StripeCheckout $stripe): RedirectResponse
    {
        abort_unless(hash_equals($order->access_token, $token), 403);

        if (! $stripe->isConfigured()) {
            return redirect($order->publicUrl())->with('status', 'Online payment is not available. Your order is saved for pay later.');
        }

        try {
            $checkoutUrl = $stripe->createSession($order);
        } catch (HttpClientException) {
            return redirect($order->publicUrl())->with('status', 'Could not start payment. Try again or pay later.');
        }

        if ($checkoutUrl === null) {
            return redirect($order->publicUrl())->with('status', 'This order can no longer be paid online.');
        }

        return redirect()->away($checkoutUrl);
    }

    /**
     * Stripe's success_url. The session id names the order, so the order's secret link never goes to Stripe.
     */
    public function return(Request $request, StripeCheckout $stripe): RedirectResponse
    {
        $sessionId = $request->string('session_id')->toString();
        $order = $sessionId !== '' ? Order::query()->where('stripe_session_id', $sessionId)->first() : null;

        abort_if($order === null, 404);

        try {
            $stripe->handleCompletedSession($sessionId, $order);
        } catch (HttpClientException) {
            return redirect($order->publicUrl())->with('status', 'We could not confirm the payment yet. It will show here once Stripe confirms it.');
        }

        return redirect($order->publicUrl());
    }
}
