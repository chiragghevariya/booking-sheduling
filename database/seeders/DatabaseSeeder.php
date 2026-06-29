<?php

namespace Database\Seeders;

use App\Models\Availability;
use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ---------------------------------------------------------------
        // Admin
        // ---------------------------------------------------------------
        User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'timezone' => 'America/New_York',
        ]);

        // ---------------------------------------------------------------
        // Provider (the business shown across the app)
        // ---------------------------------------------------------------
        $provider = User::factory()->provider()->create([
            'name' => 'Smart Booking Solutions',
            'email' => 'provider@example.com',
            'password' => Hash::make('password'),
            'phone' => '+1 (415) 555-0142',
            'timezone' => 'America/New_York',
        ]);

        // ---------------------------------------------------------------
        // Services — realistic consulting catalogue (prices in USD)
        // ---------------------------------------------------------------
        $catalogue = [
            [
                'name' => 'Business Consultation',
                'description' => 'A focused one-on-one session to understand your business challenges, review where you are today, and identify the highest-impact opportunities to grow.',
                'duration_minutes' => 30,
                'buffer_minutes' => 10,
                'price' => 49,
            ],
            [
                'name' => 'Strategy Session',
                'description' => 'A deep-dive working session to map out a clear, actionable strategy for your next quarter — covering goals, priorities, and the roadmap to get there.',
                'duration_minutes' => 60,
                'buffer_minutes' => 15,
                'price' => 99,
            ],
            [
                'name' => 'Project Planning Meeting',
                'description' => 'A collaborative planning session to define your project scope, key milestones, deliverables, and a realistic timeline your team can execute against.',
                'duration_minutes' => 45,
                'buffer_minutes' => 10,
                'price' => 79,
            ],
            [
                'name' => 'Premium Consulting Session',
                'description' => 'A comprehensive advisory session with detailed analysis of your business, tailored recommendations, and a written follow-up action plan to implement straight away.',
                'duration_minutes' => 90,
                'buffer_minutes' => 15,
                'price' => 199,
            ],
            [
                'name' => 'Team Workshop',
                'description' => 'An interactive workshop for your whole team to align on goals, sharpen processes, and boost productivity — facilitated end-to-end with practical takeaways.',
                'duration_minutes' => 120,
                'buffer_minutes' => 15,
                'price' => 349,
            ],
        ];

        $services = [];
        foreach ($catalogue as $attrs) {
            $services[$attrs['name']] = Service::factory()->create([
                'provider_id' => $provider->id,
                'name' => $attrs['name'],
                'slug' => Str::slug($attrs['name']),
                'description' => $attrs['description'],
                'duration_minutes' => $attrs['duration_minutes'],
                'buffer_minutes' => $attrs['buffer_minutes'],
                'price' => $attrs['price'],
                'currency' => 'USD',
                'is_active' => true,
            ]);
        }

        // ---------------------------------------------------------------
        // Weekly availability: Monday–Friday, 09:00–17:00
        // ---------------------------------------------------------------
        foreach ([1, 2, 3, 4, 5] as $dayOfWeek) {
            Availability::factory()->create([
                'provider_id' => $provider->id,
                'day_of_week' => $dayOfWeek,
                'start_time' => '09:00:00',
                'end_time' => '17:00:00',
                'is_active' => true,
            ]);
        }

        // ---------------------------------------------------------------
        // Customers (primary logins keep the customer1 / customer2 emails)
        // ---------------------------------------------------------------
        $priya = User::factory()->customer()->create([
            'name' => 'Sarah Johnson',
            'email' => 'customer1@example.com',
            'password' => Hash::make('password'),
            'phone' => '+1 (212) 555-0178',
            'timezone' => 'America/New_York',
        ]);

        $arjun = User::factory()->customer()->create([
            'name' => 'Michael Brown',
            'email' => 'customer2@example.com',
            'password' => Hash::make('password'),
            'phone' => '+1 (312) 555-0193',
            'timezone' => 'America/Chicago',
        ]);

        $neha = User::factory()->customer()->create([
            'name' => 'Emma Wilson',
            'email' => 'emma.wilson@example.com',
            'password' => Hash::make('password'),
            'phone' => '+1 (415) 555-0124',
            'timezone' => 'America/Los_Angeles',
        ]);

        $rahul = User::factory()->customer()->create([
            'name' => 'James Miller',
            'email' => 'james.miller@example.com',
            'password' => Hash::make('password'),
            'phone' => '+1 (617) 555-0167',
            'timezone' => 'America/New_York',
        ]);

        // ---------------------------------------------------------------
        // Bookings — a realistic mix of statuses, past and upcoming.
        // Priya (customer1) gets the richest history so the demo login
        // shows a full Bookings tab covering every status.
        // ---------------------------------------------------------------
        $book = function (User $customer, string $serviceName, Carbon $start, string $status, array $extra = []) use ($provider, $services) {
            /** @var Service $service */
            $service = $services[$serviceName];

            Booking::factory()->create(array_merge([
                'customer_id' => $customer->id,
                'provider_id' => $provider->id,
                'service_id' => $service->id,
                'starts_at' => $start,
                'ends_at' => (clone $start)->addMinutes($service->duration_minutes),
                'status' => $status,
            ], $extra));
        };

        $at = fn (int $days, int $hour, int $min = 0) => Carbon::now()->addDays($days)->setTime($hour, $min);

        // Priya — upcoming
        $book($priya, 'Business Consultation', $at(1, 10, 0), Booking::STATUS_APPROVED, [
            'approved_at' => Carbon::now()->subDay(),
            'notes' => 'Looking to streamline our onboarding process.',
        ]);
        $book($priya, 'Strategy Session', $at(3, 11, 0), Booking::STATUS_PENDING, [
            'notes' => 'Need help planning the Q3 product roadmap.',
        ]);
        $book($priya, 'Premium Consulting Session', $at(6, 14, 0), Booking::STATUS_APPROVED, [
            'approved_at' => Carbon::now()->subHours(20),
        ]);

        // Priya — past / resolved
        $book($priya, 'Project Planning Meeting', $at(-7, 15, 0), Booking::STATUS_APPROVED, [
            'approved_at' => Carbon::now()->subDays(9),
            'notes' => 'Kick-off for the website redesign project.',
        ]);
        $book($priya, 'Team Workshop', $at(4, 9, 0), Booking::STATUS_DECLINED, [
            'declined_at' => Carbon::now()->subHours(6),
            'decline_reason' => 'That slot is fully booked. Please pick another time and we will confirm right away.',
        ]);
        $book($priya, 'Business Consultation', $at(-2, 16, 0), Booking::STATUS_CANCELLED, [
            'cancelled_at' => Carbon::now()->subDays(3),
            'notes' => 'Had to reschedule due to a travel conflict.',
        ]);

        // Arjun (customer2)
        $book($arjun, 'Premium Consulting Session', $at(2, 13, 0), Booking::STATUS_PENDING, [
            'notes' => 'Scaling our operations team — need a growth plan.',
        ]);
        $book($arjun, 'Strategy Session', $at(5, 10, 0), Booking::STATUS_APPROVED, [
            'approved_at' => Carbon::now()->subHours(12),
        ]);
        $book($arjun, 'Business Consultation', $at(-5, 11, 0), Booking::STATUS_APPROVED, [
            'approved_at' => Carbon::now()->subDays(7),
        ]);

        // Other customers — fills out the provider's calendar
        $book($neha, 'Project Planning Meeting', $at(2, 9, 0), Booking::STATUS_APPROVED, [
            'approved_at' => Carbon::now()->subDay(),
        ]);
        $book($neha, 'Team Workshop', $at(8, 13, 0), Booking::STATUS_PENDING);
        $book($rahul, 'Strategy Session', $at(1, 15, 0), Booking::STATUS_APPROVED, [
            'approved_at' => Carbon::now()->subHours(30),
        ]);
        $book($rahul, 'Business Consultation', $at(-10, 10, 0), Booking::STATUS_CANCELLED, [
            'cancelled_at' => Carbon::now()->subDays(11),
        ]);
    }
}
