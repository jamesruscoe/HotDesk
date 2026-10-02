<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DeskType;
use App\Models\Desk;
use App\Models\Floor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Desk>
 */
class DeskFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'floor_id' => Floor::factory(),
            'label' => 'D-'.fake()->unique()->numberBetween(1, 9999),
            'type' => DeskType::DESK,
            'x' => fake()->numberBetween(0, 1100),
            'y' => fake()->numberBetween(0, 700),
            'is_bookable' => true,
        ];
    }

    public function assignedTo(User $user): static
    {
        return $this->state(fn (): array => ['assigned_user_id' => $user->id]);
    }
}
