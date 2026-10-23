<?php

namespace Shopmade\Shop\Services;

use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Collection;
use Shopmade\Shop\Models\Product;
use Shopmade\Shop\Support\Money;

class Cart
{
    private const SESSION_KEY = 'shop.cart';

    public function __construct(private Session $session) {}

    /**
     * @return array<int, int>
     */
    public function items(): array
    {
        /** @var array<int, int> $items */
        $items = $this->session->get(self::SESSION_KEY, []);

        return $items;
    }

    /**
     * @return Collection<int, array{product: Product, qty: int, line_total: float}>
     */
    public function lines(): Collection
    {
        $productIds = array_keys($this->items());

        if ($productIds === []) {
            return collect();
        }

        $products = Product::query()
            ->active()
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        return collect($this->items())
            ->map(function (int $qty, int $productId) use ($products) {
                $product = $products->get($productId);

                if (! $product) {
                    return null;
                }

                return [
                    'product' => $product,
                    'qty' => $qty,
                    'line_total' => Money::round(Money::round($product->price) * $qty),
                ];
            })
            ->filter()
            ->values();
    }

    public function count(): int
    {
        return (int) $this->lines()->sum('qty');
    }

    public function total(): float
    {
        return (float) $this->lines()->sum('line_total');
    }

    public function add(Product $product, int $qty = 1): void
    {
        $items = $this->items();
        $current = $items[$product->id] ?? 0;
        $items[$product->id] = $current + $qty;
        $this->session->put(self::SESSION_KEY, $items);
    }

    public function update(Product $product, int $qty): void
    {
        $items = $this->items();

        if ($qty <= 0) {
            unset($items[$product->id]);
        } else {
            $items[$product->id] = $qty;
        }

        $this->session->put(self::SESSION_KEY, $items);
    }

    public function remove(Product $product): void
    {
        $items = $this->items();
        unset($items[$product->id]);
        $this->session->put(self::SESSION_KEY, $items);
    }

    public function clear(): void
    {
        $this->session->forget(self::SESSION_KEY);
    }

    /**
     * Empty when nothing in the cart is still an active product.
     */
    public function isEmpty(): bool
    {
        return $this->lines()->isEmpty();
    }
}
