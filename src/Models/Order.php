<?php

namespace Shopmade\Shop\Models;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Shopmade\Shop\Database\Factories\OrderFactory;
use Shopmade\Shop\Enums\OrderStatus;

#[UseFactory(OrderFactory::class)]
#[Fillable([
    'customer_id',
    'guest_name',
    'guest_email',
    'guest_phone',
    'address',
    'notes',
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
            'offline_settlement_at' => 'datetime',
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

    /**
     * Moves a new order to paid, under a row lock. A fulfilled order that was never paid keeps its status and gets
     * its paid_at. A payment for a cancelled order (or a second payment) is not treated as success: it is logged for
     * a refund and false is returned.
     */
    public function markPaid(?string $stripeSessionId = null): bool
    {
        return DB::transaction(function () use ($stripeSessionId): bool {
            $order = self::query()->lockForUpdate()->findOrFail($this->id);

            $sameSession = $stripeSessionId === null || $stripeSessionId === $order->stripe_session_id;

            if (($order->status === OrderStatus::Paid || ($order->status === OrderStatus::Fulfilled && $order->paid_at !== null)) && $sameSession) {
                return true;
            }

            if ($order->status === OrderStatus::Fulfilled && $order->paid_at === null) {
                $order->forceFill([
                    'paid_at' => now(),
                    'stripe_session_id' => $stripeSessionId ?? $order->stripe_session_id,
                ])->save();
                $this->setRawAttributes($order->getAttributes(), true);

                return true;
            }

            if ($order->status !== OrderStatus::New) {
                Log::error('Payment received for an order that is not new; refund it in Stripe.', [
                    'order' => $order->number,
                    'status' => $order->status->value,
                    'stripe_session_id' => $stripeSessionId,
                    'paid_session_id' => $order->stripe_session_id,
                ]);

                return false;
            }

            $order->forceFill([
                'status' => OrderStatus::Paid,
                'paid_at' => now(),
                'stripe_session_id' => $stripeSessionId ?? $order->stripe_session_id,
            ])->save();

            $this->setRawAttributes($order->getAttributes(), true);

            return true;
        });
    }

    /**
     * Claims a new order for a payment staff took outside Stripe, before its Checkout Session is expired. While the
     * claim is set no new session opens and an expired-session event doesn't cancel the order. Returns the session id
     * to close, or false when the order is no longer new.
     */
    public function claimOfflineSettlement(): string|false|null
    {
        return DB::transaction(function (): string|false|null {
            $order = self::query()->lockForUpdate()->findOrFail($this->id);

            if ($order->status !== OrderStatus::New) {
                return false;
            }

            $order->forceFill(['offline_settlement_at' => now()])->save();
            $this->setRawAttributes($order->getAttributes(), true);

            return $order->stripe_session_id;
        });
    }

    public function releaseOfflineSettlement(): void
    {
        self::query()->whereKey($this->id)->update(['offline_settlement_at' => null]);
        $this->offline_settlement_at = null;
    }

    /**
     * Staff recorded a payment taken outside Stripe (cash, check). Close the order's Checkout Session first and pass
     * the id that was closed; the mark is refused, quietly, unless the order is still new with that same session.
     */
    public function markPaidByStaff(?string $closedSessionId): bool
    {
        return DB::transaction(function () use ($closedSessionId): bool {
            $order = self::query()->lockForUpdate()->findOrFail($this->id);

            if ($order->status !== OrderStatus::New || $order->stripe_session_id !== $closedSessionId) {
                return false;
            }

            $order->forceFill(['status' => OrderStatus::Paid, 'paid_at' => now(), 'offline_settlement_at' => null])->save();
            $this->setRawAttributes($order->getAttributes(), true);

            return true;
        });
    }

    public function markFulfilled(): bool
    {
        return DB::transaction(function (): bool {
            $order = self::query()->lockForUpdate()->findOrFail($this->id);

            if ($order->status !== OrderStatus::Paid && $order->status !== OrderStatus::New) {
                return false;
            }

            $order->forceFill(['status' => OrderStatus::Fulfilled])->save();
            $this->setRawAttributes($order->getAttributes(), true);

            return true;
        });
    }

    /**
     * Cancels an unpaid order and puts its stock back. A paid order is refused: refund it in Stripe first. Close any
     * open Checkout Session (StripeCheckout::closeSession) before calling this and pass the id that was closed: if
     * another session was saved meanwhile, the cancel is refused so that session can be closed too. $abandoned is
     * for an expired-session event: it never cancels an order staff are settling offline.
     */
    public function markCancelled(?string $closedSessionId = null, bool $abandoned = false): bool
    {
        return DB::transaction(function () use ($closedSessionId, $abandoned): bool {
            $order = self::query()->lockForUpdate()->findOrFail($this->id);

            if ($order->status !== OrderStatus::New || $order->stripe_session_id !== $closedSessionId) {
                return false;
            }

            if ($abandoned && $order->offline_settlement_at !== null) {
                return false;
            }

            $order->load('items.product');

            foreach ($order->items as $item) {
                $item->product?->restock((int) $item->qty);
            }

            $order->forceFill(['status' => OrderStatus::Cancelled, 'offline_settlement_at' => null])->save();
            $this->setRawAttributes($order->getAttributes(), true);

            return true;
        });
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
