<?php

namespace Shopmade\Shop\Enums;

enum ProductType: string
{
    case Physical = 'physical';
    case ServicePackage = 'service_package';

    public function label(): string
    {
        return match ($this) {
            self::Physical => 'Physical',
            self::ServicePackage => 'Service package',
        };
    }
}
