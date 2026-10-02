<?php

declare(strict_types=1);

namespace App\Enums;

enum BookingStatus: string
{
    case CONFIRMED = 'confirmed';
    case CHECKED_IN = 'checked_in';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case NO_SHOW = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::CONFIRMED => 'Confirmed',
            self::CHECKED_IN => 'Checked in',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
            self::NO_SHOW => 'No show',
        };
    }

    /**
     * Whether a booking in this status still holds its desk for its time slot.
     */
    public function holdsDesk(): bool
    {
        return match ($this) {
            self::CONFIRMED, self::CHECKED_IN, self::COMPLETED => true,
            self::CANCELLED, self::NO_SHOW => false,
        };
    }

    /**
     * @return array<int, self>
     */
    public static function holdingDesk(): array
    {
        return array_values(array_filter(self::cases(), fn (self $status): bool => $status->holdsDesk()));
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
