<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Shopmade\Shop\Models\Product;
use Tests\TestCase;

pest()->extend(TestCase::class)->use(RefreshDatabase::class);

it('renders the shop pages inside the public site layout, without the app bundle', function () {
    $product = Product::factory()->create(['name' => 'Lean Shirt', 'active' => true]);

    foreach ([route('shop.index'), route('shop.show', $product), route('shop.cart')] as $url) {
        $this->get($url)
            ->assertOk()
            ->assertSee('class="site-header"', false)
            ->assertSee('class="sm-shop"', false)
            ->assertSee('<meta name="robots" content="noindex">', false)
            ->assertDontSee('/build/assets/', false);
    }
});
