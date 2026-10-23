@extends('shop::layouts.shop')

@section('title', 'Shop')
@section('heading', 'Shop')
@section('lede', 'Browse products and checkout as a guest.')

@section('shop')
    @if ($products->isEmpty())
        <div class="sm-shop-empty">
            <h2 class="sm-shop-h3">No products available</h2>
            <p>Check back soon.</p>
        </div>
    @else
        <ul class="sm-shop-grid" role="list">
            @foreach ($products as $product)
                <li>
                    <a href="{{ route('shop.show', $product) }}" class="sm-shop-card">
                        @include('shop::shop.partials.media', ['product' => $product, 'lazy' => true])
                        <span class="sm-shop-card-head">{{ $product->type->label() }}</span>
                        <span class="sm-shop-card-body">
                            <span class="sm-shop-card-name">{{ $product->name }}</span>
                            <span class="sm-shop-card-desc">{{ Str::limit($product->description, 80) }}</span>
                            <span class="sm-shop-card-foot">
                                <span class="sm-shop-price">{{ \Shopmade\Shop\Support\Money::format($product->price) }}</span>
                                <span class="sm-shop-card-cta" aria-hidden="true">View &rarr;</span>
                            </span>
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
@endsection
