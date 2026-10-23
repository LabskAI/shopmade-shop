<?php

namespace Shopmade\Shop\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Shopmade\Shop\Models\Order;
use Shopmade\Shop\Services\StripeCheckout;

class StripeCheckoutController extends Controller
{
    public function store(Order $order, StripeCheckout $stripe): RedirectResponse
    {
        if (! $stripe->isConfigured()) {
            return redirect()->route('orders.public.show', [
                'order' => $order->id,
                'token' => $order->access_token,
            ])->with('status', 'Online payment is not available. Your order is saved for pay later.');
        }

        if ($order->status->value === 'paid') {
            return redirect($order->publicUrl())->with('status', 'This order is already paid.');
        }

        try {
            $checkoutUrl = $stripe->createSession($order);
        } catch (RequestException) {
            return redirect($order->publicUrl())->with('status', 'Could not start payment. Try again or pay later.');
        }

        return redirect()->away($checkoutUrl);
    }
}
