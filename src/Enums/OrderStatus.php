<?php

namespace Shopmade\Shop\Enums;

enum OrderStatus: string
{
    case New = 'new';
    case Paid = 'paid';
    case Fulfilled = 'fulfilled';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Paid => 'Paid',
            self::Fulfilled => 'Fulfilled',
            self::Cancelled => 'Cancelled',
        };
    }
}
