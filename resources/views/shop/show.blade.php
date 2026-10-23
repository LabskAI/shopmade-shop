@extends('shop::layouts.shop')

@section('title', $product->name)
@section('crumb', $product->name)
@section('heading', $product->name)

@section('shop')
    <div class="sm-shop-product {{ $product->image_url ? 'has-media' : '' }}">
        @if ($product->image_url)
            <div class="sm-shop-product-media">
                @include('shop::shop.partials.media', ['product' => $product])
            </div>
        @else
            <div class="sm-shop-product-about">
                <span class="sm-shop-tag">{{ $product->type->label() }}</span>
                <p class="sm-shop-lead">{{ $product->description }}</p>
            </div>
        @endif
        <div class="sm-shop-product-info sm-shop-summary">
            @if ($product->image_url)
                <span class="sm-shop-tag">{{ $product->type->label() }}</span>
            @endif
            <p class="sm-shop-price sm-shop-price-lg">{{ \Shopmade\Shop\Support\Money::format($product->price) }}</p>
            @if ($product->image_url)
                <p class="sm-shop-body">{{ $product->description }}</p>
            @endif
            @if ($product->tracksStock())
                <p class="sm-shop-meta">{{ $product->stock }} in stock</p>
            @endif

            <form method="POST" action="{{ route('shop.cart.store', $product) }}" class="sm-shop-add">
                @csrf
                <div class="sm-shop-field sm-shop-field-qty">
                    <label class="sm-shop-label" for="qty">Quantity</label>
                    <input class="sm-shop-input" id="qty" name="qty" type="number" min="1" max="99" value="1" inputmode="numeric">
                </div>
                <button class="sm-shop-btn" type="submit">Add to cart</button>
            </form>
        </div>
    </div>
@endsection
