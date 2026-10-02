<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Floor;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Floor>
 */
class FloorFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'location_id' => Location::factory(),
            'name' => 'Ground floor',
            'level' => 0,
            'width' => 1200,
            'height' => 800,
            'layout' => ['elements' => []],
        ];
    }
}
