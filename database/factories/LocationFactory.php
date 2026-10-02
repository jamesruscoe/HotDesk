<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DayOfWeek;
use App\Models\Location;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Location>
 */
class LocationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->city().' Office',
            'address' => fake()->address(),
            'timezone' => 'Europe/London',
            'settings' => null,
        ];
    }

    /**
     * Monday to Friday 09:00-17:00, closed at weekends, unless hours are
     * given explicitly with withHours().
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Location $location): void {
            if ($location->hours()->exists()) {
                return;
            }

            foreach (DayOfWeek::cases() as $day) {
                $location->hours()->create([
                    'day_of_week' => $day,
                    'opens_at' => '09:00',
                    'closes_at' => '17:00',
                    'is_closed' => $day->isWeekend(),
                ]);
            }
        });
    }

    /**
     * The same opening hours every day of the week.
     */
    public function withHours(string $opensAt, string $closesAt): static
    {
        return $this->afterCreating(function (Location $location) use ($opensAt, $closesAt): void {
            foreach (DayOfWeek::cases() as $day) {
                $location->hours()->updateOrCreate(
                    ['day_of_week' => $day],
                    ['opens_at' => $opensAt, 'closes_at' => $closesAt, 'is_closed' => false],
                );
            }
        });
    }

    /**
     * @param array<string, mixed> $settings
     */
    public function withSettings(array $settings): static
    {
        return $this->state(fn (): array => ['settings' => $settings]);
    }
}
