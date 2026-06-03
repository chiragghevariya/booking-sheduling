<?php

namespace App\Providers;

use App\Models\AvailabilityException;
use App\Policies\AvailabilityPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // AvailabilityException reuses AvailabilityPolicy via custom abilities
        // (viewException/updateException/deleteException). Laravel's auto-discovery
        // only maps a model to its <Model>Policy, so we wire this one explicitly.
        Gate::policy(AvailabilityException::class, AvailabilityPolicy::class);
    }
}
