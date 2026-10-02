<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\BookingType;
use RuntimeException;

/**
 * The booking breaks a rule of the desk or location, independent of what
 * else is booked.
 */
class BookingNotAllowedException extends RuntimeException
{
    public static function deskNotBookable(): self
    {
        return new self('This desk is not available for booking.');
    }

    public static function deskAssignedToSomeoneElse(): self
    {
        return new self('This desk is permanently assigned to someone else.');
    }

    public static function noAccess(): self
    {
        return new self('You do not have access to this location.');
    }

    public static function typeDisabled(BookingType $type): self
    {
        return new self(sprintf('%s bookings are not enabled at this location.', $type->label()));
    }

    public static function multiDayDisabled(): self
    {
        return new self('Multi-day bookings are not enabled at this location.');
    }

    public static function notOnTheHour(): self
    {
        return new self('Hourly bookings must start and end on the hour.');
    }

    public static function invalidRange(): self
    {
        return new self('The booking must end after it starts.');
    }

    public static function inThePast(): self
    {
        return new self('You cannot book a time that has already passed.');
    }

    public static function tooFarAhead(int $maxDaysAhead): self
    {
        return new self(sprintf('Bookings can be made at most %d days ahead.', $maxDaysAhead));
    }

    public static function outsideWorkingHours(): self
    {
        return new self('That time is outside the office working hours.');
    }

    public static function closed(): self
    {
        return new self('The office is closed for the whole of that period.');
    }

    public static function addOnUnavailable(): self
    {
        return new self('One of the selected add-ons is not offered at this location.');
    }

    public static function notCancellable(): self
    {
        return new self('This booking can no longer be cancelled.');
    }

    public static function checkInDisabled(): self
    {
        return new self('Check-in is not enabled at this location.');
    }

    public static function outsideCheckInWindow(): self
    {
        return new self('Check-in is not open for this booking right now.');
    }
}
