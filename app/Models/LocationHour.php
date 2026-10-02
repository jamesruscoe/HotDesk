<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DayOfWeek;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocationHour extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'day_of_week' => DayOfWeek::class,
            'is_closed' => 'boolean',
        ];
    }

    /** @return BelongsTo<Location, $this> */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
