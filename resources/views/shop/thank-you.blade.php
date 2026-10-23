@extends('shop::layouts.shop')

@section('title', 'Order confirmed')
@section('crumb', 'Order confirmed')
@section('heading', 'Thank you!')

@section('shop')
    <div class="sm-shop-narrow">
        <p class="sm-shop-body">Order <strong>{{ $order->number }}</strong> has been placed.</p>

        <section class="sm-shop-panel" aria-labelledby="order-link-title">
            <h2 class="sm-shop-h3" id="order-link-title">Your order link</h2>
            <p class="sm-shop-body">Save this link to check your order status:</p>
            <a class="sm-shop-order-url" href="{{ $order->publicUrl() }}">{{ $order->publicUrl() }}</a>
        </section>

        <div class="sm-shop-actions">
            <a class="sm-shop-btn" href="{{ $order->publicUrl() }}">View order</a>
            <a class="sm-shop-btn sm-shop-btn-line" href="{{ route('shop.index') }}">Continue shopping</a>
        </div>
    </div>
@endsection
