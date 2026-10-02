<?php

declare(strict_types=1);

namespace App\Enums;

enum AddOnPricingUnit: string
{
    case PER_BOOKING = 'per_booking';
    case PER_DAY = 'per_day';
    case PER_HOUR = 'per_hour';

    public function label(): string
    {
        return match ($this) {
            self::PER_BOOKING => 'Per booking',
            self::PER_DAY => 'Per day',
            self::PER_HOUR => 'Per hour',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
