<?php

namespace App\Services;

use App\Mail\BookingApproved;
use App\Mail\BookingCancelled;
use App\Mail\BookingDeclined;
use App\Mail\BookingRequested;
use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/**
 * Business logic for booking lifecycle: create (request), reschedule, cancel.
 *
 * Slot-conflict checks are performed inside a DB transaction with a row-level
 * lock on overlapping rows so two customers can't simultaneously request the
 * same slot.
 */
class BookingService
{
    public function __construct(
        private readonly SlotAvailabilityService $slots,
        private readonly ExpoPushService $push,
    ) {
    }

    /**
     * Create a new booking in PENDING state. Verifies the slot is still open
     * against weekly availability, exceptions, and existing pending/approved
     * bookings (with buffer) — all under a transaction with a row lock.
     *
     * @throws ValidationException when the slot is no longer bookable
     */
    public function request(User $customer, Service $service, Carbon $startsAt, array $extras = []): Booking
    {
        $endsAt = $startsAt->copy()->addMinutes($service->duration_minutes);
        $provider = $service->provider;
        $buffer = $service->buffer_minutes;

        return DB::transaction(function () use ($customer, $service, $provider, $startsAt, $endsAt, $buffer, $extras) {
            // Pending requests are "soft holds": the slot is hidden from the calendar
            // (subtracted in SlotAvailabilityService) but doesn't prevent another
            // customer from requesting the same time. Only APPROVED bookings block
            // a new request — competing pending requests get sorted out via the
            // provider's approve/auto-decline flow in Phase 5.
            $hardConflict = Booking::query()
                ->where('provider_id', $provider->id)
                ->where('status', Booking::STATUS_APPROVED)
                ->where(function ($q) use ($startsAt, $endsAt, $buffer) {
                    $q->where('starts_at', '<', $endsAt->copy()->addMinutes($buffer))
                      ->where('ends_at', '>', $startsAt->copy()->subMinutes($buffer));
                })
                ->lockForUpdate()
                ->exists();

            if ($hardConflict) {
                throw ValidationException::withMessages([
                    'starts_at' => 'That slot is no longer available. Please pick another time.',
                ]);
            }

            // Also verify the slot falls inside computed availability — defends against
            // requests that bypassed the UI (e.g. crafted API call with an off-hours time).
            $this->assertWithinAvailability($provider, $service, $startsAt);

            $booking = Booking::create([
                'customer_id' => $customer->id,
                'provider_id' => $provider->id,
                'service_id' => $service->id,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => Booking::STATUS_PENDING,
                'notes' => $extras['notes'] ?? null,
            ]);

            // Update customer's phone if provided and they didn't have one.
            if (! empty($extras['phone']) && empty($customer->phone)) {
                $customer->forceFill(['phone' => $extras['phone']])->save();
            }

            // Queued notification to the provider. Mailable details land in Phase 6.
            Mail::to($provider->email)->queue(new BookingRequested($booking->fresh(['customer', 'service'])));

            return $booking;
        });
    }

    /**
     * Move a still-pending booking to a new start time. Same conflict rules apply,
     * but the booking being moved is excluded from the conflict check.
     *
     * @throws ValidationException
     */
    public function reschedule(Booking $booking, Carbon $newStart): Booking
    {
        $service = $booking->service;
        $newEnd = $newStart->copy()->addMinutes($service->duration_minutes);
        $buffer = $service->buffer_minutes;

        return DB::transaction(function () use ($booking, $service, $newStart, $newEnd, $buffer) {
            // Same "soft hold" rule as request(): only approved bookings block.
            $hardConflict = Booking::query()
                ->where('provider_id', $booking->provider_id)
                ->where('id', '!=', $booking->id)
                ->where('status', Booking::STATUS_APPROVED)
                ->where(function ($q) use ($newStart, $newEnd, $buffer) {
                    $q->where('starts_at', '<', $newEnd->copy()->addMinutes($buffer))
                      ->where('ends_at', '>', $newStart->copy()->subMinutes($buffer));
                })
                ->lockForUpdate()
                ->exists();

            if ($hardConflict) {
                throw ValidationException::withMessages([
                    'starts_at' => 'That slot is no longer available. Please pick another time.',
                ]);
            }

            $this->assertWithinAvailability($booking->provider, $service, $newStart);

            $booking->forceFill([
                'starts_at' => $newStart,
                'ends_at' => $newEnd,
                // Rescheduling always re-enters pending so the provider re-approves.
                'status' => Booking::STATUS_PENDING,
                'approved_at' => null,
            ])->save();

            return $booking->fresh(['service', 'provider', 'customer']);
        });
    }

    public function cancel(Booking $booking): Booking
    {
        $booking->forceFill([
            'status' => Booking::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ])->save();

        return $booking->fresh();
    }

    /**
     * Provider/admin approves a pending booking. Inside a transaction:
     *   1. Lock the target row and re-check it's still pending.
     *   2. Lock all other pending bookings on this provider whose [start,end]
     *      window (expanded by buffer) overlaps the approved slot.
     *   3. Mark each of those as declined with reason "Slot no longer available."
     *      and queue the BookingDeclined email.
     *   4. Mark the target as approved and queue the BookingApproved email.
     *
     * Returns the freshly-loaded approved booking plus the collection that was
     * auto-declined so callers can surface the count in a toast / UI.
     *
     * @return array{booking: Booking, autoDeclined: Collection<int, Booking>}
     */
    public function approve(Booking $booking): array
    {
        return DB::transaction(function () use ($booking) {
            /** @var Booking $locked */
            $locked = Booking::query()->lockForUpdate()->findOrFail($booking->id);

            if (! $locked->isPending()) {
                throw ValidationException::withMessages([
                    'status' => 'Only pending bookings can be approved.',
                ]);
            }

            $service = $locked->service;
            $buffer = $service?->buffer_minutes ?? 0;

            // Find any *other* pending bookings overlapping this slot — these are
            // the ones we need to auto-decline. Approved bookings can't conflict
            // here because store() and reschedule() already prevented that, but
            // pending requests can pile up on the same time.
            $overlapping = Booking::query()
                ->where('provider_id', $locked->provider_id)
                ->where('id', '!=', $locked->id)
                ->where('status', Booking::STATUS_PENDING)
                ->where(function ($q) use ($locked, $buffer) {
                    $q->where('starts_at', '<', $locked->ends_at->copy()->addMinutes($buffer))
                      ->where('ends_at', '>', $locked->starts_at->copy()->subMinutes($buffer));
                })
                ->lockForUpdate()
                ->with(['customer', 'service', 'provider'])
                ->get();

            foreach ($overlapping as $other) {
                $other->forceFill([
                    'status' => Booking::STATUS_DECLINED,
                    'declined_at' => now(),
                    'decline_reason' => 'Slot no longer available — another request was approved.',
                ])->save();

                $otherFresh = $other->fresh(['customer', 'service', 'provider']);
                Mail::to($other->customer->email)->queue(new BookingDeclined($otherFresh));
                // Push: notify the auto-declined customer too.
                $this->push->bookingDeclined($otherFresh);
            }

            $locked->forceFill([
                'status' => Booking::STATUS_APPROVED,
                'approved_at' => now(),
                'declined_at' => null,
                'decline_reason' => null,
            ])->save();

            $approved = $locked->fresh(['customer', 'service', 'provider']);
            Mail::to($approved->customer->email)->queue(new BookingApproved($approved));
            // Push: send to every Expo token registered for this customer.
            $this->push->bookingApproved($approved);

            return [
                'booking' => $approved,
                'autoDeclined' => $overlapping,
            ];
        });
    }

    /** Provider/admin declines a pending booking with an optional reason. */
    public function decline(Booking $booking, ?string $reason = null): Booking
    {
        return DB::transaction(function () use ($booking, $reason) {
            /** @var Booking $locked */
            $locked = Booking::query()->lockForUpdate()->findOrFail($booking->id);

            if (! $locked->isPending()) {
                throw ValidationException::withMessages([
                    'status' => 'Only pending bookings can be declined.',
                ]);
            }

            $locked->forceFill([
                'status' => Booking::STATUS_DECLINED,
                'declined_at' => now(),
                'decline_reason' => $reason,
            ])->save();

            $fresh = $locked->fresh(['customer', 'service', 'provider']);
            Mail::to($fresh->customer->email)->queue(new BookingDeclined($fresh));
            // Push: notify the customer their request was declined.
            $this->push->bookingDeclined($fresh);

            return $fresh;
        });
    }

    /** Provider/admin cancels an approved booking; emails the customer. */
    public function providerCancel(Booking $booking): Booking
    {
        $fresh = $this->cancel($booking)->fresh(['customer', 'service', 'provider']);
        Mail::to($fresh->customer->email)->queue(new BookingCancelled($fresh, 'customer'));

        return $fresh;
    }

    /** Customer cancels their own booking; emails the provider. */
    public function customerCancel(Booking $booking): Booking
    {
        $fresh = $this->cancel($booking)->fresh(['customer', 'service', 'provider']);
        Mail::to($fresh->provider->email)->queue(new BookingCancelled($fresh, 'provider'));

        return $fresh;
    }

    private function assertWithinAvailability(User $provider, Service $service, Carbon $startsAt): void
    {
        // Validate against weekly hours + exceptions ONLY. Pending bookings are
        // soft holds and approved bookings are already enforced by the
        // hardConflict check in request()/reschedule().
        $slots = $this->slots->slotsFor(
            $provider,
            $service,
            $startsAt->copy()->startOfDay(),
            $startsAt->copy()->endOfDay(),
            ['ignoreBookings' => true],
        );

        $match = collect($slots)->contains(
            fn ($s) => Carbon::parse($s['starts_at'])->equalTo($startsAt),
        );

        if (! $match) {
            throw ValidationException::withMessages([
                'starts_at' => 'That time is outside the provider\'s availability.',
            ]);
        }
    }
}
