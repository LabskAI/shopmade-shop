{{--
    Shop pages (shop, product, cart, checkout, thank-you, public order page) inside the public site's own layout:
    its theme tokens, header and footer, from the front's home page. ShopLayoutComposer supplies that data, or a bare
    stand-in when no front is mounted.
--}}
@php
    $cartCount = app(\Shopmade\Shop\Services\Cart::class)->count();
    $title = html_entity_decode(trim($__env->yieldContent('title', 'Shop')), ENT_QUOTES).' · '.$name;
    $description = html_entity_decode(trim($__env->yieldContent('description')), ENT_QUOTES) ?: $description;
@endphp

@extends('front.layout')

@section('content')
    @include('shop::partials.styles')
    <div class="sm-shop">
        <section class="sm-shop-band" aria-labelledby="shop-title">
            <div class="wrap sm-shop-band-row">
                <div class="sm-shop-band-text">
                    <nav aria-label="Breadcrumb" class="sm-shop-crumbs">
                        <a href="{{ route('shop.index') }}">Shop</a>
                        @hasSection('crumb')
                            <span aria-hidden="true">/</span>
                            <span aria-current="page">@yield('crumb')</span>
                        @endif
                    </nav>
                    <h1 class="sm-h2 sm-shop-title" id="shop-title">@yield('heading', 'Shop')</h1>
                    @hasSection('lede')
                        <p class="sm-intro-p sm-shop-lede">@yield('lede')</p>
                    @endif
                </div>
                <a class="sm-btn sm-btn-line sm-btn-sm sm-shop-cart-link" href="{{ route('shop.cart') }}">
                    <span>Cart</span>
                    @if ($cartCount > 0)
                        <span class="sm-shop-cart-count">{{ $cartCount }}</span><span class="sm-sr"> {{ Str::plural('item', $cartCount) }}</span>
                    @endif
                </a>
            </div>
        </section>

        <div class="sm-shop-paper">
            <div class="wrap">
                @if ($errors->any())
                    <div class="sm-shop-notice sm-shop-notice-bad" role="alert">
                        <p class="sm-shop-notice-title">Something went wrong</p>
                        <p>{{ $errors->first() }}</p>
                    </div>
                @endif
                @if (session('status'))
                    <p class="sm-shop-notice" role="status">{{ session('status') }}</p>
                @endif
                @yield('shop')
            </div>
        </div>
    </div>
@endsection
