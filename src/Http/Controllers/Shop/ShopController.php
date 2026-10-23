<?php

namespace Shopmade\Shop\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Shopmade\Shop\Models\Product;

class ShopController extends Controller
{
    public function index(): View
    {
        $products = Product::query()
            ->active()
            ->orderBy('name')
            ->get();

        return view('shop::shop.index', compact('products'));
    }

    public function show(Product $product): View
    {
        abort_unless($product->active, 404);

        return view('shop::shop.show', compact('product'));
    }
}
