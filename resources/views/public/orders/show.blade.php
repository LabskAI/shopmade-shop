@extends('shop::layouts.shop')

@section('title', 'Order '.$order->number)
@section('crumb', 'Order')
@section('heading', $order->number)
@section('lede', 'Placed '.$order->created_at->format('M j, Y'))

@section('shop')
    <div class="sm-shop-narrow">
        <p class="sm-shop-status sm-shop-status-{{ $order->status->value }}">{{ $order->status->label() }}</p>

        <section class="sm-shop-panel" aria-labelledby="items-title">
            <h2 class="sm-shop-h3" id="items-title">Items</h2>
            <ul class="sm-shop-sum-lines" role="list">
                @foreach ($order->items as $item)
                    <li><span>{{ $item->name }} × {{ $item->qty }}</span><span class="sm-shop-num">{{ \Shopmade\Shop\Support\Money::format($item->line_total) }}</span></li>
                @endforeach
            </ul>
            <div class="sm-shop-total">
                <span>Total</span>
                <span class="sm-shop-price">{{ \Shopmade\Shop\Support\Money::format($order->total) }}</span>
            </div>
        </section>

        <section class="sm-shop-panel" aria-labelledby="delivery-title">
            <h2 class="sm-shop-h3" id="delivery-title">Delivery</h2>
            <dl class="sm-shop-dl">
                <div><dt>Name</dt><dd>{{ $order->guest_name }}</dd></div>
                <div><dt>Address</dt><dd>{{ $order->address }}</dd></div>
            </dl>
        </section>

        @if ($stripeEnabled && $order->status->value === 'new')
            <form method="POST" action="{{ route('orders.stripe.store', ['order' => $order, 'token' => $order->access_token]) }}" class="sm-shop-actions">
                @csrf
                <button class="sm-shop-btn" type="submit">Pay with Stripe</button>
            </form>
        @elseif ($order->status->value === 'new')
            <p class="sm-shop-body">Payment pending — the shop will confirm when your order is paid.</p>
        @endif
    </div>
@endsection
