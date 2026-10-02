<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\OpeningWindowDto;
use App\Models\Booking;
use App\Models\Floor;
use Illuminate\Database\Eloquent\Collection;

/**
 * What the booking screen needs to grey out taken slots before submit. The
 * server re-checks on submit, so this is for the user, not for safety.
 */
final class AvailabilityService
{
    public function __construct(
        private readonly WorkingHoursService $workingHoursService,
    ) {}

    public function windowFor(Floor $floor, string $localDate): ?OpeningWindowDto
    {
        return $this->workingHoursService->windowFor($floor->location, $localDate);
    }

    /**
     * Live bookings on the floor's desks that touch the local date.
     *
     * @return Collection<int, Booking>
     */
    public function bookingsOn(Floor $floor, string $localDate): Collection
    {
        $window = $this->windowFor($floor, $localDate);

        if ($window === null) {
            return new Collection();
        }

        return Booking::query()
            ->whereIn('desk_id', $floor->desks()->select('id'))
            ->holdingDesk()
            ->overlapping($window->startsAt, $window->endsAt)
            ->with('user:id,name')
            ->orderBy('starts_at')
            ->get();
    }
}
