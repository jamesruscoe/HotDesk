<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * How a location offers an add-on. A location that does not offer an add-on
 * at all simply has no location_add_ons row for it.
 */
enum AddOnAvailability: string
{
    case INCLUDED = 'included';
    case OPTIONAL = 'optional';

    public function label(): string
    {
        return match ($this) {
            self::INCLUDED => 'Included',
            self::OPTIONAL => 'Optional extra',
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
