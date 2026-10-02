<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * ISO-8601 day numbers, matching Carbon's dayOfWeekIso.
 */
enum DayOfWeek: int
{
    case MONDAY = 1;
    case TUESDAY = 2;
    case WEDNESDAY = 3;
    case THURSDAY = 4;
    case FRIDAY = 5;
    case SATURDAY = 6;
    case SUNDAY = 7;

    public function label(): string
    {
        return match ($this) {
            self::MONDAY => 'Monday',
            self::TUESDAY => 'Tuesday',
            self::WEDNESDAY => 'Wednesday',
            self::THURSDAY => 'Thursday',
            self::FRIDAY => 'Friday',
            self::SATURDAY => 'Saturday',
            self::SUNDAY => 'Sunday',
        };
    }

    public function isWeekend(): bool
    {
        return match ($this) {
            self::SATURDAY, self::SUNDAY => true,
            self::MONDAY, self::TUESDAY, self::WEDNESDAY, self::THURSDAY, self::FRIDAY => false,
        };
    }

    /**
     * @return array<int, int>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
