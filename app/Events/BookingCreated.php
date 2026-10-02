<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Booking;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A payment listener hooks in here later: bookings with a price start in
 * PaymentStatus::PENDING and wait for it.
 */
class BookingCreated implements ShouldDispatchAfterCommit
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly Booking $booking) {}
}
