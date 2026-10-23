<?php

namespace Shopmade\Shop\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Shopmade\Shop\Enums\OrderStatus;
use Shopmade\Shop\Models\Order;
use Shopmade\Shop\Services\PaymentMismatch;
use Shopmade\Shop\Services\SessionStillOpen;
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
        $claim = $order->claimOfflineSettlement();

        if ($claim === 'closed') {
            return $this->back($order, 'Only a new order can be marked paid.');
        }

        if ($claim === 'held') {
            return $this->back($order, 'This order is already being marked paid. Try again in a minute.');
        }

        try {
            $closed = $stripe->closeSession($order);
        } catch (SessionStillOpen $exception) {
            report($exception);
            $order->releaseOfflineSettlement($claim['token']);

            return $this->back($order, 'Stripe kept the payment page open, so the order was not marked paid. Try again.');
        } catch (HttpClientException $exception) {
            report($exception);

            return $this->back($order, 'Stripe did not answer, so the order is held unpaid and can\'t be paid online meanwhile. Try again in a minute.');
        } catch (PaymentMismatch $exception) {
            report($exception);
            $order->releaseOfflineSettlement($claim['token']);

            return $this->back($order, 'Stripe has a card payment for this order that doesn\'t match it, so nothing was recorded. Check the refund log and Stripe before marking it paid.');
        }

        if ($closed && $order->markPaidByStaff($claim['token'], $claim['session'])) {
            return $this->back($order, 'Order marked paid.');
        }

        $order->releaseOfflineSettlement($claim['token']);
        $order->refresh();

        if ($order->status === OrderStatus::Paid || $order->paid_at !== null) {
            return $this->back($order, 'The buyer already paid by card, so that payment was recorded.');
        }

        if ($order->status === OrderStatus::Cancelled) {
            return $this->back($order, 'The order was cancelled meanwhile, so it was not marked paid.');
        }

        return $this->back($order, $closed
            ? 'Only a new order can be marked paid.'
            : 'Stripe shows this checkout as complete but has no payment to record yet. Check it in Stripe before marking it paid.');
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
            return $this->back($order, $order->paidByCard()
                ? 'Paid orders can\'t be cancelled here. Refund the payment in Stripe and keep the order for your records.'
                : 'Paid orders can\'t be cancelled here. Refund the buyer directly and keep the order for your records.');
        }

        if ($order->status !== OrderStatus::New) {
            return redirect()->route('orders.show', $order)->with('status', 'Only an unpaid order can be cancelled.');
        }

        $closedSessionId = $order->stripe_session_id;

        if ($order->hasFreshOfflineSettlement()) {
            return $this->back($order, 'This order is being marked paid right now, so it was not cancelled. Reload it in a minute.');
        }

        try {
            $closed = $stripe->closeSession($order);
        } catch (HttpClientException|SessionStillOpen $exception) {
            report($exception);

            return redirect()->route('orders.show', $order)->with('status', 'Could not close the Stripe payment page, so the order was not cancelled. Try again.');
        } catch (PaymentMismatch $exception) {
            report($exception);

            return $this->back($order, 'Stripe has a card payment for this order that doesn\'t match it, so the order was not cancelled. Check the refund log and Stripe.');
        }

        if (! $closed) {
            return redirect()->route('orders.show', $order)->with('status', 'The buyer has already paid, so the order was not cancelled.');
        }

        $done = $order->markCancelled($closedSessionId);

        if (! $done && $order->fresh()->hasFreshOfflineSettlement()) {
            return $this->back($order, 'This order is being marked paid right now, so it was not cancelled. Reload it in a minute.');
        }

        if (! $done && $order->fresh()->status === OrderStatus::New) {
            return redirect()->route('orders.show', $order)->with('status', 'The buyer opened a new payment page while you were cancelling. Try again.');
        }

        return redirect()->route('orders.show', $order)->with('status', $done ? 'Order cancelled. Its stock was put back.' : 'Only an unpaid order can be cancelled.');
    }
}
