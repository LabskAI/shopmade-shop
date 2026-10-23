<?php

namespace Shopmade\Shop\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Shopmade\Shop\Database\Factories\ProductFactory;
use Shopmade\Shop\Enums\ProductType;

#[UseFactory(ProductFactory::class)]
#[Fillable(['name', 'slug', 'description', 'price', 'sku', 'image_url', 'type', 'stock', 'active'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => ProductType::class,
            'price' => 'decimal:2',
            'stock' => 'integer',
            'active' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function tracksStock(): bool
    {
        return $this->type === ProductType::Physical && $this->stock !== null;
    }

    public function isInStock(int $qty = 1): bool
    {
        if (! $this->tracksStock()) {
            return true;
        }

        return $this->stock >= $qty;
    }

    /**
     * Takes stock with a guarded UPDATE, so two checkouts can't oversell. False when there isn't enough left.
     */
    public function decrementStock(int $qty): bool
    {
        if (! $this->tracksStock()) {
            return true;
        }

        $taken = self::query()->whereKey($this->id)->where('stock', '>=', $qty)->decrement('stock', $qty) === 1;

        if ($taken) {
            $this->stock -= $qty;
        }

        return $taken;
    }

    public function restock(int $qty): void
    {
        if ($this->tracksStock()) {
            self::query()->whereKey($this->id)->increment('stock', $qty);
        }
    }
}
