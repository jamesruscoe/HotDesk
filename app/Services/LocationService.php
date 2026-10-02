<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\LocationSettingsDto;
use App\Enums\DayOfWeek;
use App\Models\Location;
use App\Models\LocationAddOn;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class LocationService
{
    private const DEFAULT_OPENS_AT = '09:00';
    private const DEFAULT_CLOSES_AT = '17:00';

    /**
     * @return Collection<int, Location>
     */
    public function listFor(Organization $organization): Collection
    {
        return $organization->locations()->withCount('floors')->orderBy('name')->get();
    }

    /**
     * New offices open Monday to Friday 09:00-17:00 until their hours are edited.
     *
     * @param array{name: string, address?: string|null, timezone: string} $data
     */
    public function create(Organization $organization, array $data, User $actor): Location
    {
        return DB::transaction(function () use ($organization, $data, $actor): Location {
            $location = $organization->locations()->create([
                'name' => $data['name'],
                'address' => $data['address'] ?? null,
                'timezone' => $data['timezone'],
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            foreach (DayOfWeek::cases() as $day) {
                $location->hours()->create([
                    'day_of_week' => $day,
                    'opens_at' => self::DEFAULT_OPENS_AT,
                    'closes_at' => self::DEFAULT_CLOSES_AT,
                    'is_closed' => $day->isWeekend(),
                ]);
            }

            return $location;
        });
    }

    public function settingsFor(Location $location): LocationSettingsDto
    {
        $location->loadMissing('organization');

        return LocationSettingsDto::fromLayers($location->organization->settings, $location->settings);
    }

    public function isAccessibleBy(Location $location, User $user): bool
    {
        return Location::query()->accessibleBy($user)->whereKey($location->id)->exists();
    }

    /**
     * The office's active add-on offers, for attaching to desks.
     *
     * @return Collection<int, LocationAddOn>
     */
    public function activeAddOnOffers(Location $location): Collection
    {
        return $location->addOns()->active()->with('addOn')->get();
    }
}
