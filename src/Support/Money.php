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

    /**
     * Stripe's zero-decimal currencies: amounts are sent in whole units.
     */
    private const array ZeroDecimal = ['bif', 'clp', 'djf', 'gnf', 'jpy', 'kmf', 'krw', 'mga', 'pyg', 'rwf', 'vnd', 'vuv', 'xaf', 'xof', 'xpf'];

    /**
     * Two-decimal on the wire, but Stripe charges them only in whole units (multiples of 100 minor units).
     */
    private const array WholeUnitsOnly = ['isk', 'ugx'];

    /**
     * The amount rounded to what Stripe can charge in the configured currency, so stored totals equal the charge.
     */
    public static function round(float|int|string|null $amount): float
    {
        $currency = self::currency();
        $decimals = in_array($currency, [...self::ZeroDecimal, ...self::WholeUnitsOnly], true) ? 0 : 2;

        return round((float) $amount, $decimals);
    }

    /**
     * The amount in the currency's smallest unit, as Stripe expects it.
     */
    public static function minorUnits(float|int|string|null $amount): int
    {
        $multiplier = in_array(self::currency(), self::ZeroDecimal, true) ? 1 : 100;

        return (int) round(self::round($amount) * $multiplier);
    }

    private static function currency(): string
    {
        return strtolower((string) config('shop.stripe.currency', 'usd'));
    }
}
