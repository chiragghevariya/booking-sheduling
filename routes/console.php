<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Auto-expire pending bookings that have been sitting too long.
// Runs hourly; the command is idempotent so partial runs are safe.
Schedule::command('bookings:expire-stale')
    ->hourly()
    ->withoutOverlapping()
    ->onOneServer();
