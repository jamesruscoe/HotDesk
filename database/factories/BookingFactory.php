<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Enums\BookingType;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Desk;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = now()->addDay()->setTime(9, 0);

        return [
            'desk_id' => Desk::factory(),
            'user_id' => User::factory(),
            'type' => BookingType::HOURLY,
            'status' => BookingStatus::CONFIRMED,
            'starts_at' => $start,
            'ends_at' => $start->addHours(2),
            'price_cents' => 0,
            'payment_status' => PaymentStatus::NOT_REQUIRED,
        ];
    }
}
