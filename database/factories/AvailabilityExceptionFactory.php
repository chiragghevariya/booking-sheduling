<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AvailabilityException>
 */
class AvailabilityExceptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'provider_id' => User::factory()->provider(),
            'date' => fake()->dateTimeBetween('+1 day', '+30 days')->format('Y-m-d'),
            'start_time' => null,
            'end_time' => null,
            'type' => 'blocked',
            'reason' => fake()->optional()->sentence(4),
        ];
    }
}
