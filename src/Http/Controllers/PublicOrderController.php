<?php

namespace Shopmade\Shop\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Shopmade\Shop\Models\Order;
use Shopmade\Shop\Services\StripeCheckout;

class PublicOrderController extends Controller
{
    public function show(Order $order, string $token, StripeCheckout $stripe): View
    {
        abort_unless(hash_equals($order->access_token, $token), 403);

        $order->load('items');

        return view('shop::public.orders.show', [
            'order' => $order,
            'stripeEnabled' => $stripe->isConfigured(),
        ]);
    }
}
