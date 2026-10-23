<?php

namespace Shopmade\Shop\Support;

use Illuminate\Support\Number;

/**
 * Formats shop amounts in the configured Stripe currency (shop.stripe.currency).
 */
final class Money
{
    public static function format(float|int|string|null $amount): string
    {
        $currency = strtoupper((string) config('shop.stripe.currency', 'usd'));

        return (string) Number::currency((float) $amount, in: $currency, locale: 'en');
    }
}
