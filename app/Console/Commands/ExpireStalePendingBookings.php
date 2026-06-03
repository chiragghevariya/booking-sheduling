<?php

namespace App\Console\Commands;

use App\Mail\BookingDeclined;
use App\Models\Booking;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Auto-expires pending bookings older than a configurable threshold.
 * Frees the soft-held slot and emails the customer that their request lapsed.
 *
 * Threshold: BOOKING_PENDING_EXPIRY_HOURS env var (default 24).
 */
class ExpireStalePendingBookings extends Command
{
    protected $signature = 'bookings:expire-stale
                            {--hours= : Override the BOOKING_PENDING_EXPIRY_HOURS env value}
                            {--dry-run : Report what would be expired without making changes}';

    protected $description = 'Auto-declines pending bookings older than the configured threshold (default 24h).';

    public function handle(): int
    {
        $hours = (int) ($this->option('hours') ?? config('bookings.pending_expiry_hours', 24));

        if ($hours <= 0) {
            $this->error('Threshold must be > 0 hours.');
            return self::INVALID;
        }

        $threshold = now()->subHours($hours);

        $query = Booking::query()
            ->where('status', Booking::STATUS_PENDING)
            ->where('created_at', '<=', $threshold)
            ->with(['customer', 'service', 'provider']);

        $count = (clone $query)->count();
        $this->info("Found {$count} pending booking(s) older than {$hours}h (created on/before {$threshold->toDateTimeString()}).");

        if ($count === 0 || $this->option('dry-run')) {
            if ($this->option('dry-run')) {
                $this->line('Dry run — no changes were made.');
            }
            return self::SUCCESS;
        }

        DB::transaction(function () use ($query) {
            $stale = $query->lockForUpdate()->get();

            foreach ($stale as $booking) {
                $booking->forceFill([
                    'status' => Booking::STATUS_DECLINED,
                    'declined_at' => now(),
                    'decline_reason' => 'Request expired — the provider did not respond in time.',
                ])->save();

                // Queued notification to the customer.
                if ($booking->customer?->email) {
                    Mail::to($booking->customer->email)->queue(
                        new BookingDeclined($booking->fresh(['customer', 'service', 'provider'])),
                    );
                }
            }

            $this->info("Expired {$stale->count()} booking(s).");
        });

        return self::SUCCESS;
    }
}
