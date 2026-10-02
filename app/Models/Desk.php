<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DeskType;
use Database\Factories\DeskFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Desk extends Model
{
    /** @use HasFactory<DeskFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $hidden = ['qr_token'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => DeskType::class,
            'x' => 'float',
            'y' => 'float',
            'width' => 'float',
            'height' => 'float',
            'rotation' => 'float',
            'amenities' => 'array',
            'is_bookable' => 'boolean',
            'hourly_rate_cents' => 'integer',
            'daily_rate_cents' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Desk $desk): void {
            $desk->qr_token ??= Str::random(40);
        });
    }

    /** @return BelongsTo<Floor, $this> */
    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    /** @return BelongsTo<User, $this> */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    /** @return HasMany<DeskAddOn, $this> */
    public function addOns(): HasMany
    {
        return $this->hasMany(DeskAddOn::class);
    }

    /** @return HasMany<Booking, $this> */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    #[Scope]
    protected function bookable(Builder $builder): Builder
    {
        return $builder->where('is_bookable', true);
    }
}
