<?php

namespace Shopmade\Shop\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Shopmade\Shop\Enums\OrderStatus;
use Shopmade\Shop\Models\Order;

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

    public function markPaid(Order $order): RedirectResponse
    {
        $order->markPaid();

        return redirect()->route('orders.show', $order)->with('status', 'Order marked paid.');
    }

    public function markFulfilled(Order $order): RedirectResponse
    {
        $order->markFulfilled();

        return redirect()->route('orders.show', $order)->with('status', 'Order marked fulfilled.');
    }

    public function markCancelled(Order $order): RedirectResponse
    {
        $order->markCancelled();

        return redirect()->route('orders.show', $order)->with('status', 'Order cancelled.');
    }
}
