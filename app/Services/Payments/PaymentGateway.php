<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Models\Booking;
use App\Models\Payment;

/**
 * The seam for a real payment provider. Adding Stripe later means one class
 * implementing this and changing the binding in AppServiceProvider.
 */
interface PaymentGateway
{
    public function name(): string;

    public function isEnabled(): bool;

    public function charge(Booking $booking): Payment;

    public function refund(Payment $payment): Payment;
}
