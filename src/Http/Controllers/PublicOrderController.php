<?php

namespace Shopmade\Shop\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Shopmade\Shop\Models\Order;
use Shopmade\Shop\Services\StripeCheckout;

class PublicOrderController extends Controller
{
    public function show(Request $request, Order $order, string $token, StripeCheckout $stripe): View
    {
        abort_unless(hash_equals($order->access_token, $token), 403);

        if ($stripe->isConfigured() && $request->filled('session_id')) {
            $stripe->handleCompletedSession($request->string('session_id')->toString());
            $order->refresh();
        }

        $order->load('items');

        return view('shop::public.orders.show', [
            'order' => $order,
            'stripeEnabled' => $stripe->isConfigured(),
        ]);
    }
}
