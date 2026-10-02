<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AddOnAvailability;
use App\Enums\AddOnPricingUnit;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An add-on attached to one desk, through its office's offer of it.
 */
class DeskAddOn extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'availability' => AddOnAvailability::class,
            'price_cents' => 'integer',
        ];
    }

    /** @return BelongsTo<Desk, $this> */
    public function desk(): BelongsTo
    {
        return $this->belongsTo(Desk::class);
    }

    /** @return BelongsTo<LocationAddOn, $this> */
    public function locationAddOn(): BelongsTo
    {
        return $this->belongsTo(LocationAddOn::class);
    }

    public function effectiveAvailability(): AddOnAvailability
    {
        return $this->availability ?? $this->locationAddOn->availability;
    }

    public function effectivePriceCents(): ?int
    {
        return $this->price_cents ?? $this->locationAddOn->price_cents;
    }

    public function pricingUnit(): AddOnPricingUnit
    {
        return $this->locationAddOn->pricing_unit;
    }
}
