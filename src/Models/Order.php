<?php

namespace Shopmade\Shop\Models;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Shopmade\Shop\Database\Factories\OrderFactory;
use Shopmade\Shop\Enums\OrderStatus;

#[UseFactory(OrderFactory::class)]
#[Fillable([
    'number',
    'status',
    'customer_id',
    'guest_name',
    'guest_email',
    'guest_phone',
    'address',
    'notes',
    'total',
    'stripe_session_id',
    'access_token',
    'paid_at',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'total' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order): void {
            if (blank($order->number)) {
                $order->number = self::generateNumber();
            }

            if (blank($order->access_token)) {
                $order->access_token = Str::random(64);
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function publicUrl(): string
    {
        return route('orders.public.show', [
            'order' => $this->id,
            'token' => $this->access_token,
        ]);
    }

    public function markPaid(?string $stripeSessionId = null): void
    {
        if ($this->status === OrderStatus::Paid) {
            return;
        }

        $this->update([
            'status' => OrderStatus::Paid,
            'paid_at' => now(),
            'stripe_session_id' => $stripeSessionId ?? $this->stripe_session_id,
        ]);
    }

    public function markFulfilled(): void
    {
        $this->update(['status' => OrderStatus::Fulfilled]);
    }

    public function markCancelled(): void
    {
        $this->update(['status' => OrderStatus::Cancelled]);
    }

    public static function generateNumber(): string
    {
        $latest = self::query()->orderByDesc('id')->value('number');
        $sequence = 1;

        if ($latest && preg_match('/(\d+)$/', $latest, $matches)) {
            $sequence = (int) $matches[1] + 1;
        }

        return sprintf('ORD-%s-%04d', now()->format('Ymd'), $sequence);
    }
}
