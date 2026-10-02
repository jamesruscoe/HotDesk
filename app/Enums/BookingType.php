<?php

declare(strict_types=1);

namespace App\Enums;

enum BookingType: string
{
    case HOURLY = 'hourly';
    case FULL_DAY = 'full_day';

    public function label(): string
    {
        return match ($this) {
            self::HOURLY => 'Hourly',
            self::FULL_DAY => 'Full day',
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
