@extends('shop::layouts.shop')

@section('title', 'Cart')
@section('crumb', 'Cart')
@section('heading', 'Cart')

@section('shop')
    @if ($lines->isEmpty())
        <div class="sm-shop-empty">
            <h2 class="sm-shop-h3">Your cart is empty</h2>
            <p>Browse the shop and add something.</p>
            <a class="sm-shop-btn sm-shop-btn-line" href="{{ route('shop.index') }}">Browse products</a>
        </div>
    @else
        <div class="sm-shop-split">
            <ul class="sm-shop-lines" role="list">
                @foreach ($lines as $line)
                    <li class="sm-shop-line">
                        <div class="sm-shop-line-name">
                            <a href="{{ route('shop.show', $line['product']) }}">{{ $line['product']->name }}</a>
                            <p class="sm-shop-meta">{{ \Shopmade\Shop\Support\Money::format($line['product']->price) }} each</p>
                        </div>
                        <form method="POST" action="{{ route('shop.cart.update', $line['product']) }}" class="sm-shop-line-qty">
                            @csrf
                            @method('PUT')
                            <label class="sm-sr" for="qty-{{ $line['product']->id }}">Quantity for {{ $line['product']->name }}</label>
                            <input class="sm-shop-input" id="qty-{{ $line['product']->id }}" name="qty" type="number" min="0" max="99" value="{{ $line['qty'] }}" inputmode="numeric">
                            <button class="sm-shop-btn sm-shop-btn-line sm-shop-btn-sm" type="submit">Update</button>
                        </form>
                        <p class="sm-shop-line-total">{{ \Shopmade\Shop\Support\Money::format($line['line_total']) }}</p>
                        <form method="POST" action="{{ route('shop.cart.destroy', $line['product']) }}" class="sm-shop-line-remove">
                            @csrf
                            @method('DELETE')
                            <button class="sm-shop-link-btn" type="submit">Remove<span class="sm-sr"> {{ $line['product']->name }}</span></button>
                        </form>
                    </li>
                @endforeach
            </ul>

            <aside class="sm-shop-summary" aria-label="Cart total">
                <div class="sm-shop-total">
                    <span>Total</span>
                    <span class="sm-shop-price">{{ \Shopmade\Shop\Support\Money::format($total) }}</span>
                </div>
                <a class="sm-shop-btn sm-shop-btn-block" href="{{ route('shop.checkout') }}">Checkout</a>
                <a class="sm-shop-text-link" href="{{ route('shop.index') }}">Continue shopping</a>
            </aside>
        </div>
    @endif
@endsection
