<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * The requested slot overlaps a booking that already holds the desk, or one
 * the user already holds elsewhere.
 */
class BookingConflictException extends RuntimeException
{
    public static function deskTaken(): self
    {
        return new self('This desk is already booked for part of that time.');
    }

    /**
     * @param list<string> $localDates
     */
    public static function deskTakenOn(array $localDates): self
    {
        return new self('This desk is already booked on: '.implode(', ', $localDates).'.');
    }

    public static function userAlreadyBooked(): self
    {
        return new self('You already have a booking that overlaps this time.');
    }
}
