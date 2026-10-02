<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\BookingType;
use App\Enums\PaymentStatus;
use Carbon\CarbonInterface;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;
    use SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => BookingType::class,
            'status' => BookingStatus::class,
            'payment_status' => PaymentStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'checked_in_at' => 'datetime',
            'checked_out_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'price_cents' => 'integer',
        ];
    }

    /** @return BelongsTo<Desk, $this> */
    public function desk(): BelongsTo
    {
        return $this->belongsTo(Desk::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<BookingAddOn, $this> */
    public function addOns(): HasMany
    {
        return $this->hasMany(BookingAddOn::class);
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Bookings that still hold their desk (not cancelled or a no-show).
     */
    #[Scope]
    protected function holdingDesk(Builder $builder): Builder
    {
        return $builder->whereIn('status', BookingStatus::holdingDesk());
    }

    /**
     * Half-open overlap: [start, end) intersects [starts_at, ends_at), so
     * back-to-back bookings do not count as overlapping.
     */
    #[Scope]
    protected function overlapping(Builder $builder, CarbonInterface $start, CarbonInterface $end): Builder
    {
        return $builder->where('starts_at', '<', $end)->where('ends_at', '>', $start);
    }
}
