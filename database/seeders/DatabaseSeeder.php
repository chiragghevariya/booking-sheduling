<?php

namespace Database\Seeders;

use App\Models\Availability;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1 admin
        User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);

        // 1 provider
        $provider = User::factory()->provider()->create([
            'name' => 'Pro Vider',
            'email' => 'provider@example.com',
            'password' => Hash::make('password'),
            'timezone' => 'UTC',
        ]);

        // 3 sample services for the provider
        $services = [
            ['name' => 'Intro Consultation', 'duration_minutes' => 30, 'price' => 0],
            ['name' => 'Strategy Session', 'duration_minutes' => 60, 'price' => 149],
            ['name' => 'Deep Work Block', 'duration_minutes' => 90, 'price' => 249],
        ];

        foreach ($services as $attrs) {
            Service::factory()->create([
                'provider_id' => $provider->id,
                'name' => $attrs['name'],
                'slug' => Str::slug($attrs['name']),
                'duration_minutes' => $attrs['duration_minutes'],
                'buffer_minutes' => 10,
                'price' => $attrs['price'],
                'currency' => 'USD',
                'is_active' => true,
            ]);
        }

        // Weekly availability: Monday-Friday 09:00-17:00
        foreach ([1, 2, 3, 4, 5] as $dayOfWeek) {
            Availability::factory()->create([
                'provider_id' => $provider->id,
                'day_of_week' => $dayOfWeek,
                'start_time' => '09:00:00',
                'end_time' => '17:00:00',
                'is_active' => true,
            ]);
        }

        // 2 sample customers
        User::factory()->customer()->create([
            'name' => 'Casey Customer',
            'email' => 'customer1@example.com',
            'password' => Hash::make('password'),
        ]);

        User::factory()->customer()->create([
            'name' => 'Robin Returns',
            'email' => 'customer2@example.com',
            'password' => Hash::make('password'),
        ]);
    }
}
