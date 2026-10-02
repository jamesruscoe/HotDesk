<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AddOnAvailability;
use App\Enums\AddOnPricingUnit;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A location's offer of one add-on: included free, or an optional extra.
 */
class LocationAddOn extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'availability' => AddOnAvailability::class,
            'pricing_unit' => AddOnPricingUnit::class,
            'price_cents' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Location, $this> */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /** @return BelongsTo<AddOn, $this> */
    public function addOn(): BelongsTo
    {
        return $this->belongsTo(AddOn::class);
    }

    /** @return HasMany<DeskAddOn, $this> */
    public function deskAttachments(): HasMany
    {
        return $this->hasMany(DeskAddOn::class);
    }

    #[Scope]
    protected function active(Builder $builder): Builder
    {
        return $builder->where('is_active', true);
    }
}
