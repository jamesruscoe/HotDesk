<?php

declare(strict_types=1);

namespace Tests\Feature\Bookings;

use App\Enums\AddOnAvailability;
use App\Enums\AddOnPricingUnit;
use App\Enums\BookingStatus;
use App\Enums\OrganizationRole;
use App\Enums\PaymentStatus;
use App\Exceptions\BookingConflictException;
use App\Exceptions\BookingNotAllowedException;
use App\Models\AddOn;
use App\Models\Booking;
use App\Models\Desk;
use App\Models\Floor;
use App\Models\Location;
use App\Models\Organization;
use App\Models\User;
use App\Services\BookingService;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingServiceTest extends TestCase
{
    use RefreshDatabase;

    private const TZ = 'Europe/London';

    private BookingService $service;

    private Organization $organization;

    private Location $location;

    private Desk $desk;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Monday 5 October 2026, 08:00 in London (BST, UTC+1).
        $this->travelTo(CarbonImmutable::parse('2026-10-05 08:00', self::TZ));

        $this->service = $this->app->make(BookingService::class);
        $this->organization = Organization::factory()->create();
        $this->location = Location::factory()->for($this->organization)->create();
        $this->desk = $this->deskAt($this->location);
        $this->user = $this->memberOf($this->organization);
    }

    public function test_hourly_booking_is_stored_in_utc(): void
    {
        $booking = $this->service->bookHours($this->desk, $this->user, $this->local('2026-10-05 10:00'), $this->local('2026-10-05 12:00'), $this->user);

        $this->assertSame('2026-10-05T09:00:00Z', $booking->starts_at->toIso8601ZuluString());
        $this->assertSame('2026-10-05T11:00:00Z', $booking->ends_at->toIso8601ZuluString());
        $this->assertSame(BookingStatus::CONFIRMED, $booking->status);
        $this->assertSame(PaymentStatus::NOT_REQUIRED, $booking->payment_status);
    }

    public function test_overlapping_hourly_booking_on_the_same_desk_is_rejected(): void
    {
        $this->service->bookHours($this->desk, $this->user, $this->local('2026-10-05 10:00'), $this->local('2026-10-05 12:00'), $this->user);
        $other = $this->memberOf($this->organization);

        $this->expectException(BookingConflictException::class);

        $this->service->bookHours($this->desk, $other, $this->local('2026-10-05 11:00'), $this->local('2026-10-05 13:00'), $other);
    }

    public function test_back_to_back_bookings_do_not_conflict(): void
    {
        $other = $this->memberOf($this->organization);

        $this->service->bookHours($this->desk, $this->user, $this->local('2026-10-05 10:00'), $this->local('2026-10-05 12:00'), $this->user);
        $this->service->bookHours($this->desk, $other, $this->local('2026-10-05 12:00'), $this->local('2026-10-05 14:00'), $other);

        $this->assertSame(2, Booking::query()->count());
    }

    public function test_hourly_booking_must_be_on_the_hour(): void
    {
        $this->expectExceptionObject(BookingNotAllowedException::notOnTheHour());

        $this->service->bookHours($this->desk, $this->user, $this->local('2026-10-05 10:30'), $this->local('2026-10-05 12:00'), $this->user);
    }

    public function test_hourly_booking_must_be_within_working_hours(): void
    {
        $this->expectExceptionObject(BookingNotAllowedException::outsideWorkingHours());

        $this->service->bookHours($this->desk, $this->user, $this->local('2026-10-05 16:00'), $this->local('2026-10-05 18:00'), $this->user);
    }

    public function test_hourly_booking_cannot_start_in_a_past_hour(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-05 11:20', self::TZ));

        $this->expectExceptionObject(BookingNotAllowedException::inThePast());

        $this->service->bookHours($this->desk, $this->user, $this->local('2026-10-05 10:00'), $this->local('2026-10-05 12:00'), $this->user);
    }

    public function test_walk_in_can_book_the_current_hour(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-05 11:20', self::TZ));

        $booking = $this->service->bookHours($this->desk, $this->user, $this->local('2026-10-05 11:00'), $this->local('2026-10-05 13:00'), $this->user);

        $this->assertSame('2026-10-05T10:00:00Z', $booking->starts_at->toIso8601ZuluString());
    }

    public function test_full_day_spans_the_location_working_hours(): void
    {
        $bookings = $this->service->bookDays($this->desk, $this->user, '2026-10-06', '2026-10-06', $this->user);

        $this->assertCount(1, $bookings);
        $this->assertSame('2026-10-06T08:00:00Z', $bookings[0]->starts_at->toIso8601ZuluString());
        $this->assertSame('2026-10-06T16:00:00Z', $bookings[0]->ends_at->toIso8601ZuluString());
        $this->assertNull($bookings[0]->booking_group_id);
    }

    public function test_full_day_in_a_24_hour_office_runs_midnight_to_midnight(): void
    {
        $location = Location::factory()->for($this->organization)->withHours('00:00', '24:00')->create();
        $desk = $this->deskAt($location);

        $bookings = $this->service->bookDays($desk, $this->user, '2026-10-06', '2026-10-06', $this->user);

        $this->assertSame('2026-10-05T23:00:00Z', $bookings[0]->starts_at->toIso8601ZuluString());
        $this->assertSame('2026-10-06T23:00:00Z', $bookings[0]->ends_at->toIso8601ZuluString());
    }

    public function test_multi_day_booking_skips_closed_days_and_shares_a_group(): void
    {
        // Friday to Monday: the weekend is closed.
        $bookings = $this->service->bookDays($this->desk, $this->user, '2026-10-09', '2026-10-12', $this->user);

        $this->assertCount(2, $bookings);
        $this->assertNotNull($bookings[0]->booking_group_id);
        $this->assertSame($bookings[0]->booking_group_id, $bookings[1]->booking_group_id);
        $this->assertSame('2026-10-12T08:00:00Z', $bookings[1]->starts_at->toIso8601ZuluString());
    }

    public function test_multi_day_booking_is_all_or_nothing(): void
    {
        $other = $this->memberOf($this->organization);
        $this->service->bookHours($this->desk, $other, $this->local('2026-10-12 14:00'), $this->local('2026-10-12 15:00'), $other);

        try {
            $this->service->bookDays($this->desk, $this->user, '2026-10-09', '2026-10-12', $this->user);
            $this->fail('Expected a conflict.');
        } catch (BookingConflictException $exception) {
            $this->assertStringContainsString('2026-10-12', $exception->getMessage());
        }

        $this->assertSame(0, $this->user->bookings()->count());
    }

    public function test_closures_are_skipped(): void
    {
        $this->location->closures()->create(['date' => '2026-10-07', 'reason' => 'Office party']);

        $this->expectExceptionObject(BookingNotAllowedException::closed());

        $this->service->bookDays($this->desk, $this->user, '2026-10-07', '2026-10-07', $this->user);
    }

    public function test_cancelled_booking_frees_the_desk(): void
    {
        $booking = $this->service->bookHours($this->desk, $this->user, $this->local('2026-10-05 10:00'), $this->local('2026-10-05 12:00'), $this->user);
        $this->service->cancel($booking, $this->user);
        $other = $this->memberOf($this->organization);

        $rebooked = $this->service->bookHours($this->desk, $other, $this->local('2026-10-05 10:00'), $this->local('2026-10-05 12:00'), $other);

        $this->assertSame(BookingStatus::CANCELLED, $booking->fresh()->status);
        $this->assertSame(BookingStatus::CONFIRMED, $rebooked->status);
    }

    public function test_user_cannot_hold_two_desks_at_once(): void
    {
        $secondDesk = Desk::factory()->for($this->desk->floor)->create();
        $this->service->bookHours($this->desk, $this->user, $this->local('2026-10-05 10:00'), $this->local('2026-10-05 12:00'), $this->user);

        $this->expectExceptionObject(BookingConflictException::userAlreadyBooked());

        $this->service->bookHours($secondDesk, $this->user, $this->local('2026-10-05 11:00'), $this->local('2026-10-05 13:00'), $this->user);
    }

    public function test_assigned_desk_can_only_be_booked_by_its_assignee(): void
    {
        $owner = $this->memberOf($this->organization);
        $desk = Desk::factory()->for($this->desk->floor)->assignedTo($owner)->create();

        $booking = $this->service->bookHours($desk, $owner, $this->local('2026-10-05 10:00'), $this->local('2026-10-05 11:00'), $owner);
        $this->assertSame($owner->id, $booking->user_id);

        $this->expectExceptionObject(BookingNotAllowedException::deskAssignedToSomeoneElse());

        $this->service->bookHours($desk, $this->user, $this->local('2026-10-05 13:00'), $this->local('2026-10-05 14:00'), $this->user);
    }

    public function test_non_member_cannot_book(): void
    {
        $outsider = User::factory()->create();

        $this->expectExceptionObject(BookingNotAllowedException::noAccess());

        $this->service->bookHours($this->desk, $outsider, $this->local('2026-10-05 10:00'), $this->local('2026-10-05 11:00'), $outsider);
    }

    public function test_member_restricted_to_another_location_cannot_book_here(): void
    {
        $otherLocation = Location::factory()->for($this->organization)->create();
        $otherLocation->restrictedTo()->attach($this->user);

        $this->expectExceptionObject(BookingNotAllowedException::noAccess());

        $this->service->bookHours($this->desk, $this->user, $this->local('2026-10-05 10:00'), $this->local('2026-10-05 11:00'), $this->user);
    }

    public function test_booking_type_can_be_disabled_per_location(): void
    {
        $location = Location::factory()->for($this->organization)->withSettings(['allowed_booking_types' => ['full_day']])->create();
        $desk = $this->deskAt($location);

        $this->expectException(BookingNotAllowedException::class);

        $this->service->bookHours($desk, $this->user, $this->local('2026-10-05 10:00'), $this->local('2026-10-05 11:00'), $this->user);
    }

    public function test_bookings_beyond_the_horizon_are_rejected(): void
    {
        $this->expectExceptionObject(BookingNotAllowedException::tooFarAhead(30));

        $this->service->bookDays($this->desk, $this->user, '2026-11-16', '2026-11-16', $this->user);
    }

    public function test_add_on_offered_by_the_office_but_not_attached_to_the_desk_is_rejected(): void
    {
        $parking = $this->offerAddOn('Parking', AddOnAvailability::OPTIONAL, 500, AddOnPricingUnit::PER_DAY, attachToDesk: false);

        $this->expectExceptionObject(BookingNotAllowedException::addOnUnavailable());

        $this->service->bookHours($this->desk, $this->user, $this->local('2026-10-05 10:00'), $this->local('2026-10-05 11:00'), $this->user, [$parking->id]);
    }

    public function test_desk_can_override_the_office_add_on_price(): void
    {
        $monitor = $this->offerAddOn('Second monitor', AddOnAvailability::OPTIONAL, 300, AddOnPricingUnit::PER_BOOKING, deskPriceCents: 100);

        $booking = $this->service->bookHours($this->desk, $this->user, $this->local('2026-10-05 10:00'), $this->local('2026-10-05 11:00'), $this->user, [$monitor->id]);

        $this->assertSame(100, $booking->price_cents);
    }

    public function test_optional_add_ons_are_priced_and_snapshotted(): void
    {
        $tea = $this->offerAddOn('Unlimited tea', AddOnAvailability::OPTIONAL, 250, AddOnPricingUnit::PER_HOUR);

        $booking = $this->service->bookHours($this->desk, $this->user, $this->local('2026-10-05 10:00'), $this->local('2026-10-05 13:00'), $this->user, [$tea->id]);

        $line = $booking->addOns()->sole();
        $this->assertSame('Unlimited tea', $line->name);
        $this->assertSame(3, $line->quantity);
        $this->assertSame(750, $line->total_cents);
        $this->assertSame(750, $booking->price_cents);
        $this->assertSame('GBP', $booking->currency);
        $this->assertSame(PaymentStatus::PENDING, $booking->payment_status);
    }

    public function test_included_add_ons_cannot_be_selected_as_extras(): void
    {
        $wifi = $this->offerAddOn('Wi-Fi', AddOnAvailability::INCLUDED, null, AddOnPricingUnit::PER_BOOKING);

        $this->expectExceptionObject(BookingNotAllowedException::addOnUnavailable());

        $this->service->bookHours($this->desk, $this->user, $this->local('2026-10-05 10:00'), $this->local('2026-10-05 11:00'), $this->user, [$wifi->id]);
    }

    public function test_database_rejects_overlaps_that_bypass_the_service(): void
    {
        $start = $this->local('2026-10-05 10:00');
        Booking::factory()->for($this->desk)->create(['starts_at' => $start, 'ends_at' => $start->addHours(2)]);

        try {
            Booking::factory()->for($this->desk)->create(['starts_at' => $start->addHour(), 'ends_at' => $start->addHours(3)]);
            $this->fail('Expected the exclusion constraint to reject the overlap.');
        } catch (QueryException $exception) {
            $this->assertSame('23P01', $exception->getCode());
        }
    }

    public function test_check_in_requires_the_setting_and_the_window(): void
    {
        $booking = $this->service->bookHours($this->desk, $this->user, $this->local('2026-10-05 10:00'), $this->local('2026-10-05 12:00'), $this->user);

        try {
            $this->service->checkIn($booking, $this->user);
            $this->fail('Expected check-in to be disabled.');
        } catch (BookingNotAllowedException $exception) {
            $this->assertEquals(BookingNotAllowedException::checkInDisabled(), $exception);
        }

        $this->location->update(['settings' => ['qr_checkin_enabled' => true]]);
        $this->travelTo(CarbonImmutable::parse('2026-10-05 09:50', self::TZ));

        $checkedIn = $this->service->checkIn($booking->fresh(), $this->user);

        $this->assertSame(BookingStatus::CHECKED_IN, $checkedIn->status);
    }

    private function local(string $dateTime): CarbonImmutable
    {
        return CarbonImmutable::parse($dateTime, self::TZ);
    }

    private function deskAt(Location $location): Desk
    {
        return Desk::factory()->for(Floor::factory()->for($location))->create();
    }

    private function memberOf(Organization $organization): User
    {
        $user = User::factory()->create();
        $organization->members()->attach($user, ['role' => OrganizationRole::MEMBER->value]);

        return $user;
    }

    private function offerAddOn(
        string $name,
        AddOnAvailability $availability,
        ?int $priceCents,
        AddOnPricingUnit $unit,
        bool $attachToDesk = true,
        ?int $deskPriceCents = null,
    ): AddOn {
        $addOn = AddOn::query()->create(['organization_id' => $this->organization->id, 'name' => $name]);
        $offer = $this->location->addOns()->create([
            'add_on_id' => $addOn->id,
            'availability' => $availability,
            'price_cents' => $priceCents,
            'pricing_unit' => $unit,
        ]);

        if ($attachToDesk) {
            $this->desk->addOns()->create(['location_add_on_id' => $offer->id, 'price_cents' => $deskPriceCents]);
        }

        return $addOn;
    }
}
