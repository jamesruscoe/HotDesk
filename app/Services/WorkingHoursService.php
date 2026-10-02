<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\OpeningWindowDto;
use App\Enums\DayOfWeek;
use App\Models\Location;
use App\Models\LocationHour;
use Carbon\CarbonImmutable;

/**
 * Turns a location's local opening hours into UTC windows. Times are built
 * with setTime() in the location's timezone, so daylight-saving days still
 * open at 09:00 local rather than drifting by an hour.
 */
final class WorkingHoursService
{
    /**
     * The location's open window on a local date (Y-m-d), or null when closed.
     */
    public function windowFor(Location $location, string $localDate): ?OpeningWindowDto
    {
        $location->loadMissing('hours');

        $day = CarbonImmutable::createFromFormat('!Y-m-d', $localDate, $location->timezone);
        $hours = $location->hours->first(
            fn (LocationHour $hour): bool => $hour->day_of_week === DayOfWeek::from($day->dayOfWeekIso),
        );

        if ($hours === null || $hours->is_closed || $this->isClosureDate($location, $localDate)) {
            return null;
        }

        $opens = $this->at($day, $hours->opens_at);
        $closes = $this->at($day, $hours->closes_at);

        if (! $closes->greaterThan($opens)) {
            return null;
        }

        return OpeningWindowDto::fromInstants($localDate, $opens, $closes);
    }

    /**
     * Open windows for every local date in [fromDate, toDate], closed days skipped.
     *
     * @return list<OpeningWindowDto>
     */
    public function windowsBetween(Location $location, string $fromDate, string $toDate): array
    {
        $windows = [];
        $date = CarbonImmutable::createFromFormat('!Y-m-d', $fromDate, $location->timezone);
        $last = CarbonImmutable::createFromFormat('!Y-m-d', $toDate, $location->timezone);

        while ($date->lessThanOrEqualTo($last)) {
            $window = $this->windowFor($location, $date->toDateString());

            if ($window !== null) {
                $windows[] = $window;
            }

            $date = $date->addDay();
        }

        return $windows;
    }

    public function localDate(Location $location, CarbonImmutable $instant): string
    {
        return $instant->setTimezone($location->timezone)->toDateString();
    }

    private function isClosureDate(Location $location, string $localDate): bool
    {
        return $location->closures()->whereDate('date', $localDate)->exists();
    }

    /**
     * "HH:MM" on the given local day. "24:00" is midnight at the end of it.
     */
    private function at(CarbonImmutable $day, string $time): CarbonImmutable
    {
        [$hour, $minute] = array_map('intval', explode(':', $time));

        if ($hour === 24) {
            return $day->addDay()->startOfDay();
        }

        return $day->setTime($hour, $minute);
    }
}
