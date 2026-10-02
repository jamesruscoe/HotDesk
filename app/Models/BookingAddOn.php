<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AddOnPricingUnit;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingAddOn extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pricing_unit' => AddOnPricingUnit::class,
            'unit_price_cents' => 'integer',
            'quantity' => 'integer',
            'total_cents' => 'integer',
        ];
    }

    /** @return BelongsTo<Booking, $this> */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /** @return BelongsTo<AddOn, $this> */
    public function addOn(): BelongsTo
    {
        return $this->belongsTo(AddOn::class);
    }
}
