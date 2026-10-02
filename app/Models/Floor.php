<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\FloorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Floor extends Model
{
    /** @use HasFactory<FloorFactory> */
    use HasFactory;
    use SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'layout' => 'array',
        ];
    }

    /** @return BelongsTo<Location, $this> */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /** @return HasMany<Desk, $this> */
    public function desks(): HasMany
    {
        return $this->hasMany(Desk::class);
    }
}
