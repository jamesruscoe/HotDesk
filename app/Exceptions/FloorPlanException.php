<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class FloorPlanException extends RuntimeException
{
    /**
     * @param list<string> $labels
     */
    public static function desksHaveUpcomingBookings(array $labels): self
    {
        return new self(sprintf(
            'These desks have upcoming bookings and cannot be removed: %s. Cancel the bookings or mark the desks as not bookable instead.',
            implode(', ', $labels),
        ));
    }

    public static function floorHasUpcomingBookings(): self
    {
        return new self('This floor has upcoming bookings and cannot be deleted.');
    }
}
