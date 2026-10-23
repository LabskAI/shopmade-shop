<?php

use Illuminate\Support\Facades\Route;
use Shopmade\Shop\Http\Controllers\OrderController;
use Shopmade\Shop\Http\Controllers\ProductController;
use Shopmade\Shop\Http\Controllers\PublicOrderController;
use Shopmade\Shop\Http\Controllers\Shop\CartController;
use Shopmade\Shop\Http\Controllers\Shop\CheckoutController;
use Shopmade\Shop\Http\Controllers\Shop\ShopController;
use Shopmade\Shop\Http\Controllers\StripeCheckoutController;
use Shopmade\Shop\Http\Controllers\StripeWebhookController;

Route::middleware('web')->group(function () {
    Route::get('shop', [ShopController::class, 'index'])->name('shop.index');
    Route::get('shop/cart', [CartController::class, 'index'])->name('shop.cart');
    Route::get('shop/checkout', [CheckoutController::class, 'create'])->name('shop.checkout');
    Route::post('shop/checkout', [CheckoutController::class, 'store'])->middleware('throttle:10,1')->name('shop.checkout.store');
    Route::get('shop/thank-you/{order}/{token}', [CheckoutController::class, 'thankYou'])->name('shop.thank-you');
    Route::get('shop/{product:slug}', [ShopController::class, 'show'])->name('shop.show');
    Route::post('shop/cart/{product:slug}', [CartController::class, 'store'])->name('shop.cart.store');
    Route::put('shop/cart/{product:slug}', [CartController::class, 'update'])->name('shop.cart.update');
    Route::delete('shop/cart/{product:slug}', [CartController::class, 'destroy'])->name('shop.cart.destroy');

    Route::get('orders/view/{order}/{token}', [PublicOrderController::class, 'show'])->name('orders.public.show');
    Route::post('orders/view/{order}/{token}/stripe', [StripeCheckoutController::class, 'store'])->middleware('throttle:10,1')->name('orders.stripe.store');
    Route::get('shop/stripe/return', [StripeCheckoutController::class, 'return'])->middleware('throttle:30,1')->name('shop.stripe.return');
});

// Stripe calls this without a session or CSRF token; the signature (or a lookup at Stripe) is the check.
Route::post('stripe/webhook', StripeWebhookController::class)->middleware('throttle:60,1')->name('stripe.webhook');

Route::middleware(['web', 'auth', 'can:staff.access'])->group(function () {
    Route::get('products', [ProductController::class, 'index'])->name('products.index');

    Route::middleware('can-manage-core:products')->group(function () {
        Route::get('products/create', [ProductController::class, 'create'])->name('products.create');
        Route::post('products', [ProductController::class, 'store'])->name('products.store');
        Route::get('products/{product:slug}/edit', [ProductController::class, 'edit'])->name('products.edit');
        Route::put('products/{product:slug}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('products/{product:slug}', [ProductController::class, 'destroy'])->name('products.destroy');
    });

    Route::get('products/{product:slug}', [ProductController::class, 'show'])->name('products.show');

    Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');

    Route::middleware('can-manage-core:orders')->group(function () {
        Route::post('orders/{order}/mark-paid', [OrderController::class, 'markPaid'])->name('orders.mark-paid');
        Route::post('orders/{order}/mark-fulfilled', [OrderController::class, 'markFulfilled'])->name('orders.mark-fulfilled');
        Route::post('orders/{order}/mark-cancelled', [OrderController::class, 'markCancelled'])->name('orders.mark-cancelled');
    });
});
