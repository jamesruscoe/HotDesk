<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\LocationSettingsDto;
use App\DTOs\OpeningWindowDto;
use App\DTOs\PriceQuoteDto;
use App\Enums\BookingStatus;
use App\Enums\BookingType;
use App\Events\BookingCancelled;
use App\Events\BookingCheckedIn;
use App\Events\BookingCreated;
use App\Exceptions\BookingConflictException;
use App\Exceptions\BookingNotAllowedException;
use App\Models\Booking;
use App\Models\Desk;
use App\Models\DeskAddOn;
use App\Models\Location;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Every booking, whatever its type, is stored as one UTC [starts_at, ends_at)
 * row on one desk within one local day. Multi-day bookings are one row per
 * open day, linked by booking_group_id.
 *
 * Double bookings are prevented twice: a locked overlap check here that
 * produces a readable error, and a Postgres exclusion constraint that makes
 * an overlap impossible even if two requests race past the check.
 */
final class BookingService
{
    /** Postgres SQLSTATE for an exclusion constraint violation. */
    private const EXCLUSION_VIOLATION = '23P01';

    public function __construct(
        private readonly LocationService $locationService,
        private readonly WorkingHoursService $workingHoursService,
        private readonly AddOnService $addOnService,
        private readonly PaymentService $paymentService,
    ) {}

    /**
     * Book whole hours on one day, inside the location's working hours.
     *
     * @param list<int> $addOnIds
     */
    public function bookHours(
        Desk $desk,
        User $user,
        CarbonImmutable $startsAt,
        CarbonImmutable $endsAt,
        User $actor,
        array $addOnIds = [],
    ): Booking {
        $location = $this->locationOf($desk);
        $settings = $this->locationService->settingsFor($location);
        $this->assertCanBook($desk, $location, $settings, $user, BookingType::HOURLY);

        $start = $startsAt->utc();
        $end = $endsAt->utc();
        $this->assertValidHourlyRange($location, $start, $end);

        $localDate = $this->workingHoursService->localDate($location, $start);
        $this->assertWithinHorizon($location, $settings, $localDate);

        $window = $this->workingHoursService->windowFor($location, $localDate);

        if ($window === null || ! $window->contains($start, $end)) {
            throw BookingNotAllowedException::outsideWorkingHours();
        }

        $extras = $this->addOnService->optionalExtras($desk, $addOnIds);

        return DB::transaction(function () use ($desk, $location, $user, $actor, $start, $end, $extras): Booking {
            $this->lockDesk($desk);

            if ($this->deskBookingsOverlapping($desk, [[$start, $end]])->exists()) {
                throw BookingConflictException::deskTaken();
            }

            if ($this->userBookingsOverlapping($user, [[$start, $end]])->exists()) {
                throw BookingConflictException::userAlreadyBooked();
            }

            return $this->createBooking($desk, $location, $user, $actor, BookingType::HOURLY, $start, $end, $extras, null);
        });
    }

    /**
     * Book the full working day on every open day from $fromDate to $toDate
     * (local Y-m-d, inclusive). Closed days and closures are skipped. All days
     * are booked or none are.
     *
     * @param list<int> $addOnIds
     * @return Collection<int, Booking>
     */
    public function bookDays(
        Desk $desk,
        User $user,
        string $fromDate,
        string $toDate,
        User $actor,
        array $addOnIds = [],
    ): Collection {
        $location = $this->locationOf($desk);
        $settings = $this->locationService->settingsFor($location);
        $this->assertCanBook($desk, $location, $settings, $user, BookingType::FULL_DAY);

        if ($toDate < $fromDate) {
            throw BookingNotAllowedException::invalidRange();
        }

        if ($fromDate !== $toDate && ! $settings->allowMultiDay) {
            throw BookingNotAllowedException::multiDayDisabled();
        }

        $today = $this->workingHoursService->localDate($location, CarbonImmutable::now());

        if ($fromDate < $today) {
            throw BookingNotAllowedException::inThePast();
        }

        $this->assertWithinHorizon($location, $settings, $toDate);

        $now = CarbonImmutable::now();
        $windows = array_values(array_filter(
            $this->workingHoursService->windowsBetween($location, $fromDate, $toDate),
            fn (OpeningWindowDto $window): bool => $window->endsAt->greaterThan($now),
        ));

        if ($windows === []) {
            throw BookingNotAllowedException::closed();
        }

        $extras = $this->addOnService->optionalExtras($desk, $addOnIds);
        $ranges = array_map(fn (OpeningWindowDto $window): array => [$window->startsAt, $window->endsAt], $windows);

        return DB::transaction(function () use ($desk, $location, $user, $actor, $windows, $ranges, $extras): Collection {
            $this->lockDesk($desk);

            $taken = $this->deskBookingsOverlapping($desk, $ranges)->get(['starts_at']);

            if ($taken->isNotEmpty()) {
                throw BookingConflictException::deskTakenOn($this->localDatesOf($location, $taken));
            }

            if ($this->userBookingsOverlapping($user, $ranges)->exists()) {
                throw BookingConflictException::userAlreadyBooked();
            }

            $groupId = count($windows) > 1 ? (string) Str::uuid() : null;

            return new Collection(array_map(
                fn (OpeningWindowDto $window): Booking => $this->createBooking(
                    $desk, $location, $user, $actor, BookingType::FULL_DAY,
                    $window->startsAt, $window->endsAt, $extras, $groupId,
                ),
                $windows,
            ));
        });
    }

    public function cancel(Booking $booking, User $actor): Booking
    {
        if ($booking->status !== BookingStatus::CONFIRMED || $booking->ends_at->lessThanOrEqualTo(now())) {
            throw BookingNotAllowedException::notCancellable();
        }

        $booking->update([
            'status' => BookingStatus::CANCELLED,
            'cancelled_at' => now(),
            'updated_by' => $actor->id,
        ]);

        BookingCancelled::dispatch($booking);

        return $booking;
    }

    public function checkIn(Booking $booking, User $actor): Booking
    {
        $location = $this->locationOf($booking->desk);
        $settings = $this->locationService->settingsFor($location);

        if (! $settings->qrCheckinEnabled) {
            throw BookingNotAllowedException::checkInDisabled();
        }

        $now = CarbonImmutable::now();
        $opensAt = $booking->starts_at->subMinutes($settings->checkinWindowMinutes);

        if ($booking->status !== BookingStatus::CONFIRMED || $now->lessThan($opensAt) || $now->greaterThanOrEqualTo($booking->ends_at)) {
            throw BookingNotAllowedException::outsideCheckInWindow();
        }

        $booking->update([
            'status' => BookingStatus::CHECKED_IN,
            'checked_in_at' => $now,
            'updated_by' => $actor->id,
        ]);

        BookingCheckedIn::dispatch($booking);

        return $booking;
    }

    private function locationOf(Desk $desk): Location
    {
        $desk->loadMissing('floor.location.organization');

        return $desk->floor->location;
    }

    private function assertCanBook(
        Desk $desk,
        Location $location,
        LocationSettingsDto $settings,
        User $user,
        BookingType $type,
    ): void {
        if (! $desk->is_bookable) {
            throw BookingNotAllowedException::deskNotBookable();
        }

        if ($desk->assigned_user_id !== null && $desk->assigned_user_id !== $user->id) {
            throw BookingNotAllowedException::deskAssignedToSomeoneElse();
        }

        if (! $this->locationService->isAccessibleBy($location, $user)) {
            throw BookingNotAllowedException::noAccess();
        }

        if (! $settings->allows($type)) {
            throw BookingNotAllowedException::typeDisabled($type);
        }
    }

    /**
     * Hours are checked in the location's timezone: in a +05:30 zone a local
     * 09:00 is not a whole UTC hour.
     */
    private function assertValidHourlyRange(Location $location, CarbonImmutable $start, CarbonImmutable $end): void
    {
        if (! $end->greaterThan($start)) {
            throw BookingNotAllowedException::invalidRange();
        }

        $localStart = $start->setTimezone($location->timezone);
        $localEnd = $end->setTimezone($location->timezone);

        if ($localStart->minute !== 0 || $localStart->second !== 0 || $localEnd->minute !== 0 || $localEnd->second !== 0) {
            throw BookingNotAllowedException::notOnTheHour();
        }

        // The current hour is still bookable, e.g. a walk-in at 10:20 can take 10:00-12:00.
        $currentHour = CarbonImmutable::now()->setTimezone($location->timezone)->startOfHour();

        if ($localStart->lessThan($currentHour)) {
            throw BookingNotAllowedException::inThePast();
        }
    }

    private function assertWithinHorizon(Location $location, LocationSettingsDto $settings, string $localDate): void
    {
        $lastBookable = CarbonImmutable::now()
            ->setTimezone($location->timezone)
            ->addDays($settings->maxDaysAhead)
            ->toDateString();

        if ($localDate > $lastBookable) {
            throw BookingNotAllowedException::tooFarAhead($settings->maxDaysAhead);
        }
    }

    /**
     * Row-locks the desk so concurrent bookings for it queue up behind this
     * transaction instead of both passing the overlap check.
     */
    private function lockDesk(Desk $desk): void
    {
        Desk::query()->whereKey($desk->id)->lockForUpdate()->first();
    }

    /**
     * @param list<array{0: CarbonImmutable, 1: CarbonImmutable}> $ranges
     * @return Builder<Booking>
     */
    private function deskBookingsOverlapping(Desk $desk, array $ranges): Builder
    {
        return $this->overlappingAny(Booking::query()->where('desk_id', $desk->id), $ranges);
    }

    /**
     * @param list<array{0: CarbonImmutable, 1: CarbonImmutable}> $ranges
     * @return Builder<Booking>
     */
    private function userBookingsOverlapping(User $user, array $ranges): Builder
    {
        return $this->overlappingAny(Booking::query()->where('user_id', $user->id), $ranges);
    }

    /**
     * @param Builder<Booking> $query
     * @param list<array{0: CarbonImmutable, 1: CarbonImmutable}> $ranges
     * @return Builder<Booking>
     */
    private function overlappingAny(Builder $query, array $ranges): Builder
    {
        return $query->holdingDesk()->where(function (Builder $any) use ($ranges): void {
            foreach ($ranges as [$start, $end]) {
                $any->orWhere(fn (Builder $range) => $range->overlapping($start, $end));
            }
        });
    }

    /**
     * @param Collection<int, Booking> $bookings
     * @return list<string>
     */
    private function localDatesOf(Location $location, Collection $bookings): array
    {
        return $bookings
            ->map(fn (Booking $booking): string => $this->workingHoursService->localDate($location, $booking->starts_at))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @param Collection<int, DeskAddOn> $extras
     */
    private function createBooking(
        Desk $desk,
        Location $location,
        User $user,
        User $actor,
        BookingType $type,
        CarbonImmutable $start,
        CarbonImmutable $end,
        Collection $extras,
        ?string $groupId,
    ): Booking {
        $deskQuote = $this->paymentService->quote($desk, $location, $type, $start, $end);
        $lines = $this->addOnService->linesFor($extras, $start, $end);
        $total = PriceQuoteDto::fromAmount(
            $deskQuote->amountCents + array_sum(array_map(fn ($line): int => $line->total_cents, $lines)),
            $deskQuote->currency ?? $location->currency ?? $location->organization->currency,
        );

        try {
            $booking = Booking::query()->create([
                'desk_id' => $desk->id,
                'user_id' => $user->id,
                'booking_group_id' => $groupId,
                'type' => $type,
                'status' => BookingStatus::CONFIRMED,
                'starts_at' => $start,
                'ends_at' => $end,
                'price_cents' => $total->amountCents,
                'currency' => $total->isFree() ? null : $total->currency,
                'payment_status' => $this->paymentService->initialStatusFor($total),
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);
        } catch (QueryException $exception) {
            if ($exception->getCode() === self::EXCLUSION_VIOLATION) {
                throw BookingConflictException::deskTaken();
            }

            throw $exception;
        }

        $booking->addOns()->saveMany($lines);

        BookingCreated::dispatch($booking);

        return $booking;
    }
}
