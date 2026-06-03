<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Service>
 */
class ServiceFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->randomElement([
            'Intro Consultation',
            'Strategy Session',
            'Deep Work Block',
            'Coaching Call',
            'Technical Review',
        ]);

        return [
            'provider_id' => User::factory()->provider(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'description' => fake()->sentence(12),
            'duration_minutes' => fake()->randomElement([15, 30, 45, 60]),
            'buffer_minutes' => fake()->randomElement([0, 5, 10, 15]),
            'price' => fake()->randomElement([0, 49, 99, 149, 199]),
            'currency' => 'USD',
            'is_active' => true,
        ];
    }
}
