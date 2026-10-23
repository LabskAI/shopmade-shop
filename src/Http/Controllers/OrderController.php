<?php

namespace Shopmade\Shop\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;
use Shopmade\Shop\Enums\OrderStatus;
use Shopmade\Shop\Models\Order;
use Shopmade\Shop\Services\StripeCheckout;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = Order::query()
            ->with(['customer', 'items'])
            ->when($request->string('status')->toString(), fn ($query, string $status) => $query->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('shop::orders.index', [
            'orders' => $orders,
            'statuses' => OrderStatus::cases(),
        ]);
    }

    public function show(Order $order): View
    {
        $order->load(['customer', 'items.product']);

        return view('shop::orders.show', compact('order'));
    }

    /**
     * Records a payment taken outside Stripe. The Stripe payment page is closed first so the buyer can't also pay by
     * card; if Stripe already took the payment, that payment is recorded instead.
     */
    public function markPaid(Order $order, StripeCheckout $stripe): RedirectResponse
    {
        if ($order->status !== OrderStatus::New) {
            return redirect()->route('orders.show', $order)->with('status', 'Only a new order can be marked paid.');
        }

        $closedSessionId = $order->stripe_session_id;

        try {
            $closed = $stripe->closeSession($order);
        } catch (HttpClientException|RuntimeException $exception) {
            report($exception);

            return redirect()->route('orders.show', $order)->with('status', 'Could not close the Stripe payment page, so the order was not marked paid. Try again.');
        }

        if (! $closed) {
            return redirect()->route('orders.show', $order)->with('status', 'The buyer already paid by card, so that payment was recorded.');
        }

        if (! $order->markPaidByStaff($closedSessionId)) {
            return redirect()->route('orders.show', $order)->with('status', $order->fresh()->status === OrderStatus::New
                ? 'The buyer opened a new payment page meanwhile. Try again.'
                : 'Only a new order can be marked paid.');
        }

        return redirect()->route('orders.show', $order)->with('status', 'Order marked paid.');
    }

    public function markFulfilled(Order $order): RedirectResponse
    {
        $done = $order->markFulfilled();

        return redirect()->route('orders.show', $order)->with('status', $done ? 'Order marked fulfilled.' : 'Only a new or paid order can be fulfilled.');
    }

    /**
     * Closes the order's Stripe payment page first, so a cancelled order can't be paid afterwards.
     */
    public function markCancelled(Order $order, StripeCheckout $stripe): RedirectResponse
    {
        if ($order->status === OrderStatus::Paid) {
            return redirect()->route('orders.show', $order)->with('status', 'Paid orders can\'t be cancelled here. Refund the payment in Stripe and keep the order for your records.');
        }

        if ($order->status !== OrderStatus::New) {
            return redirect()->route('orders.show', $order)->with('status', 'Only an unpaid order can be cancelled.');
        }

        $closedSessionId = $order->stripe_session_id;

        try {
            $closed = $stripe->closeSession($order);
        } catch (HttpClientException|RuntimeException $exception) {
            report($exception);

            return redirect()->route('orders.show', $order)->with('status', 'Could not close the Stripe payment page, so the order was not cancelled. Try again.');
        }

        if (! $closed) {
            return redirect()->route('orders.show', $order)->with('status', 'The buyer has already paid, so the order was not cancelled.');
        }

        $done = $order->markCancelled($closedSessionId);

        if (! $done && $order->fresh()->status === OrderStatus::New) {
            return redirect()->route('orders.show', $order)->with('status', 'The buyer opened a new payment page while you were cancelling. Try again.');
        }

        return redirect()->route('orders.show', $order)->with('status', $done ? 'Order cancelled. Its stock was put back.' : 'Only an unpaid order can be cancelled.');
    }
}
