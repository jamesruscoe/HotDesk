<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AddOnAvailability;
use App\Enums\AddOnPricingUnit;
use App\Enums\DeskType;
use App\Enums\OrganizationRole;
use App\Models\Location;
use App\Models\LocationAddOn;
use App\Models\Organization;
use App\Models\User;
use App\Services\LocationService;
use App\Services\OrganizationService;
use Illuminate\Database\Seeder;

/**
 * A ready-made company to click around in: Acme Workspace with a London
 * office, one drawn floor, desks, a meeting room and a few add-ons.
 *
 * Log in as test@example.com / password.
 */
class DemoSeeder extends Seeder
{
    public function run(OrganizationService $organizations, LocationService $locations): void
    {
        $owner = User::query()->firstOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User', 'password' => 'password', 'email_verified_at' => now(), 'timezone' => 'Europe/London'],
        );

        if (Organization::query()->where('name', 'Acme Workspace')->exists()) {
            return;
        }

        $organization = $organizations->create(['name' => 'Acme Workspace', 'timezone' => 'Europe/London'], $owner);
        $organization->update(['currency' => 'GBP']);

        foreach (['Sam Patel', 'Alex Morgan'] as $index => $name) {
            $member = User::factory()->create(['name' => $name, 'email' => "member{$index}@example.com"]);
            $organization->members()->attach($member, ['role' => OrganizationRole::MEMBER->value]);
        }

        $location = $locations->create($organization, ['name' => 'London Office', 'address' => '1 High Street, London', 'timezone' => 'Europe/London'], $owner);

        $wifi = $this->offer($organization, $location, 'Wi-Fi', AddOnAvailability::INCLUDED, null, AddOnPricingUnit::PER_BOOKING);
        $tea = $this->offer($organization, $location, 'Unlimited tea & coffee', AddOnAvailability::OPTIONAL, 300, AddOnPricingUnit::PER_DAY);
        $monitor = $this->offer($organization, $location, 'Second monitor', AddOnAvailability::OPTIONAL, 0, AddOnPricingUnit::PER_BOOKING);
        $catering = $this->offer($organization, $location, 'Catering', AddOnAvailability::OPTIONAL, 1500, AddOnPricingUnit::PER_BOOKING);

        $floor = $location->floors()->create([
            'name' => 'Ground floor',
            'level' => 0,
            'width' => 1000,
            'height' => 600,
            'layout' => ['elements' => [
                ['id' => 'wall-outer', 'type' => 'wall', 'points' => [40, 40, 960, 40, 960, 560, 40, 560, 40, 40], 'thickness' => 10],
                ['id' => 'wall-meeting', 'type' => 'wall', 'points' => [700, 40, 700, 260, 960, 260], 'thickness' => 6],
                ['id' => 'room-kitchen', 'type' => 'room', 'x' => 60, 'y' => 420, 'width' => 220, 'height' => 120, 'rotation' => 0, 'label' => 'Kitchen'],
                ['id' => 'label-entrance', 'type' => 'label', 'x' => 440, 'y' => 570, 'rotation' => 0, 'text' => 'Entrance', 'font_size' => 16],
            ]],
            'created_by' => $owner->id,
        ]);

        $deskNumber = 1;

        foreach ([100, 300] as $y) {
            foreach ([120, 200, 340, 420] as $x) {
                $desk = $floor->desks()->create([
                    'label' => 'D-'.$deskNumber,
                    'type' => DeskType::DESK,
                    'x' => $x,
                    'y' => $y,
                    'width' => 60,
                    'height' => 40,
                    'assigned_user_id' => $deskNumber === 1 ? $owner->id : null,
                    'created_by' => $owner->id,
                ]);

                $desk->addOns()->create(['location_add_on_id' => $wifi->id]);
                $desk->addOns()->create(['location_add_on_id' => $tea->id]);

                if ($deskNumber % 2 === 0) {
                    $desk->addOns()->create(['location_add_on_id' => $monitor->id]);
                }

                $deskNumber++;
            }
        }

        $meetingRoom = $floor->desks()->create([
            'label' => 'Room 1',
            'type' => DeskType::MEETING_ROOM,
            'x' => 760,
            'y' => 90,
            'width' => 160,
            'height' => 100,
            'created_by' => $owner->id,
        ]);
        $meetingRoom->addOns()->create(['location_add_on_id' => $wifi->id]);
        $meetingRoom->addOns()->create(['location_add_on_id' => $catering->id]);
    }

    private function offer(
        Organization $organization,
        Location $location,
        string $name,
        AddOnAvailability $availability,
        ?int $priceCents,
        AddOnPricingUnit $unit,
    ): LocationAddOn {
        $addOn = $organization->addOns()->create(['name' => $name]);

        return $location->addOns()->create([
            'add_on_id' => $addOn->id,
            'availability' => $availability,
            'price_cents' => $priceCents,
            'pricing_unit' => $unit,
        ]);
    }
}
