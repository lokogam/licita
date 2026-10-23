<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Currencies accepted for a tender budget.
 */
enum Currency: string
{
    case COP = 'COP';
    case USD = 'USD';
    case EUR = 'EUR';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $currency): string => $currency->value, self::cases());
    }
}
