<?php

namespace Shopmade\Shop\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Shopmade\Shop\Models\Product;
use Shopmade\Shop\Services\Cart;

class CartController extends Controller
{
    public function index(Cart $cart): View
    {
        return view('shop::shop.cart', [
            'lines' => $cart->lines(),
            'total' => $cart->total(),
        ]);
    }

    public function store(Request $request, Product $product, Cart $cart): RedirectResponse
    {
        abort_unless($product->active, 404);

        $data = $request->validate([
            'qty' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

        $qty = (int) ($data['qty'] ?? 1);

        if (! $product->isInStock($qty)) {
            return back()->withErrors(['qty' => 'Not enough stock available.']);
        }

        $cart->add($product, $qty);

        return redirect()->route('shop.cart')->with('status', 'Added to cart.');
    }

    public function update(Request $request, Product $product, Cart $cart): RedirectResponse
    {
        abort_unless($product->active, 404);

        $data = $request->validate([
            'qty' => ['required', 'integer', 'min:0', 'max:99'],
        ]);

        $qty = (int) $data['qty'];

        if ($qty > 0 && ! $product->isInStock($qty)) {
            return back()->withErrors(['qty' => 'Not enough stock available.']);
        }

        $cart->update($product, $qty);

        return redirect()->route('shop.cart')->with('status', 'Cart updated.');
    }

    public function destroy(Product $product, Cart $cart): RedirectResponse
    {
        $cart->remove($product);

        return redirect()->route('shop.cart')->with('status', 'Item removed.');
    }
}
