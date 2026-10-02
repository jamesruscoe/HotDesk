<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\PriceQuoteDto;
use App\Enums\BookingType;
use App\Enums\PaymentStatus;
use App\Models\Desk;
use App\Models\Location;
use App\Services\Payments\PaymentGateway;
use Carbon\CarbonImmutable;

final class PaymentService
{
    public function __construct(
        private readonly PaymentGateway $gateway,
    ) {}

    /**
     * Desk rates override location rates. With no rates set anywhere the
     * booking is free, which is every booking for now.
     */
    public function quote(
        Desk $desk,
        Location $location,
        BookingType $type,
        CarbonImmutable $startsAt,
        CarbonImmutable $endsAt,
    ): PriceQuoteDto {
        $amount = match ($type) {
            BookingType::HOURLY => $this->hourlyAmount($desk, $location, $startsAt, $endsAt),
            BookingType::FULL_DAY => $desk->daily_rate_cents ?? $location->daily_rate_cents,
        };

        if ($amount === null || $amount === 0) {
            return PriceQuoteDto::free();
        }

        return PriceQuoteDto::fromAmount($amount, $location->currency ?? $location->organization->currency);
    }

    public function initialStatusFor(PriceQuoteDto $quote): PaymentStatus
    {
        return $quote->isFree() ? PaymentStatus::NOT_REQUIRED : PaymentStatus::PENDING;
    }

    public function gateway(): PaymentGateway
    {
        return $this->gateway;
    }

    private function hourlyAmount(Desk $desk, Location $location, CarbonImmutable $startsAt, CarbonImmutable $endsAt): ?int
    {
        $rate = $desk->hourly_rate_cents ?? $location->hourly_rate_cents;

        if ($rate === null) {
            return null;
        }

        return (int) round($rate * $startsAt->diffInHours($endsAt));
    }
}
