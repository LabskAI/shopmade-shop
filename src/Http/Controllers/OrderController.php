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
        $closedSessionId = $order->claimOfflineSettlement();

        if ($closedSessionId === false) {
            return $this->back($order, 'Only a new order can be marked paid.');
        }

        try {
            $closed = $stripe->closeSession($order);
        } catch (HttpClientException|RuntimeException $exception) {
            report($exception);
            $order->releaseOfflineSettlement();

            return $this->back($order, 'Could not close the Stripe payment page, so the order was not marked paid. Try again.');
        }

        if ($closed && $order->markPaidByStaff($closedSessionId)) {
            return $this->back($order, 'Order marked paid.');
        }

        $order->releaseOfflineSettlement();
        $order->refresh();

        if ($order->status === OrderStatus::Paid || $order->paid_at !== null) {
            return $this->back($order, 'The buyer already paid by card, so that payment was recorded.');
        }

        return $this->back($order, $closed
            ? 'Only a new order can be marked paid.'
            : 'Stripe reports a completed payment that does not match this order, so nothing was recorded. Check it in Stripe.');
    }

    private function back(Order $order, string $status): RedirectResponse
    {
        return redirect()->route('orders.show', $order)->with('status', $status);
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
            return $this->back($order, filled($order->stripe_session_id)
                ? 'Paid orders can\'t be cancelled here. Refund the payment in Stripe and keep the order for your records.'
                : 'Paid orders can\'t be cancelled here. Refund the buyer directly and keep the order for your records.');
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
