<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Models\Booking;
use App\Models\Payment;
use LogicException;

/**
 * Bound while the app is free to use. Never asked to move money because
 * every booking is priced at zero until rates are configured.
 */
final class NullPaymentGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'none';
    }

    public function isEnabled(): bool
    {
        return false;
    }

    public function charge(Booking $booking): Payment
    {
        throw new LogicException('No payment provider is configured.');
    }

    public function refund(Payment $payment): Payment
    {
        throw new LogicException('No payment provider is configured.');
    }
}
