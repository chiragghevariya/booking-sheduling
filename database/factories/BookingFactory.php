<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Booking>
 */
class BookingFactory extends Factory
{
    public function definition(): array
    {
        $start = Carbon::now()->addDays(fake()->numberBetween(1, 14))->setTime(fake()->numberBetween(9, 16), 0);
        $duration = fake()->randomElement([15, 30, 45, 60]);

        return [
            'reference' => (string) Str::uuid(),
            'customer_id' => User::factory()->customer(),
            'provider_id' => User::factory()->provider(),
            'service_id' => Service::factory(),
            'starts_at' => $start,
            'ends_at' => (clone $start)->addMinutes($duration),
            'status' => Booking::STATUS_PENDING,
            'notes' => fake()->optional()->sentence(8),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => Booking::STATUS_APPROVED,
            'approved_at' => now(),
        ]);
    }

    public function declined(): static
    {
        return $this->state(fn () => [
            'status' => Booking::STATUS_DECLINED,
            'declined_at' => now(),
            'decline_reason' => 'Slot no longer available.',
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status' => Booking::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ]);
    }
}
