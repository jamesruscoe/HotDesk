<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\LocationFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Location extends Model
{
    /** @use HasFactory<LocationFactory> */
    use HasFactory;
    use SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'hourly_rate_cents' => 'integer',
            'daily_rate_cents' => 'integer',
        ];
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return HasMany<LocationHour, $this> */
    public function hours(): HasMany
    {
        return $this->hasMany(LocationHour::class);
    }

    /** @return HasMany<LocationClosure, $this> */
    public function closures(): HasMany
    {
        return $this->hasMany(LocationClosure::class);
    }

    /** @return HasMany<LocationAddOn, $this> */
    public function addOns(): HasMany
    {
        return $this->hasMany(LocationAddOn::class);
    }

    /** @return HasMany<Floor, $this> */
    public function floors(): HasMany
    {
        return $this->hasMany(Floor::class);
    }

    /**
     * Users explicitly granted this location. Empty for everyone means the
     * whole organization has access.
     *
     * @return BelongsToMany<User, $this>
     */
    public function restrictedTo(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    /**
     * Locations in the user's organizations, minus any they are not granted
     * when their access has been restricted to specific locations.
     */
    #[Scope]
    protected function accessibleBy(Builder $builder, User $user): Builder
    {
        return $builder
            ->whereIn('organization_id', $user->organizations()->select('organizations.id'))
            ->where(function (Builder $query) use ($user): void {
                $query
                    ->whereHas('restrictedTo', fn (Builder $q) => $q->whereKey($user->id))
                    ->orWhereNotExists(function ($sub) use ($user): void {
                        $sub->selectRaw('1')
                            ->from('location_user')
                            ->join('locations as restricted', 'restricted.id', '=', 'location_user.location_id')
                            ->whereColumn('restricted.organization_id', 'locations.organization_id')
                            ->where('location_user.user_id', $user->id);
                    });
            });
    }
}
