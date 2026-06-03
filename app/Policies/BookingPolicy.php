<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    /** Anyone authenticated can list; controllers scope to "their own" or "their provider's". */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Booking $booking): bool
    {
        return $user->isAdmin()
            || $user->id === $booking->customer_id
            || $user->id === $booking->provider_id;
    }

    public function create(User $user): bool
    {
        return $user->isCustomer() || $user->isAdmin();
    }

    public function reschedule(User $user, Booking $booking): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        return $user->id === $booking->customer_id && $booking->isPending();
    }

    public function cancel(User $user, Booking $booking): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        // Provider can cancel their own bookings (typically after approval).
        if ($user->isProvider() && $user->id === $booking->provider_id) {
            return in_array($booking->status, [Booking::STATUS_PENDING, Booking::STATUS_APPROVED], true);
        }
        // Customer can cancel their own.
        if ($user->id === $booking->customer_id) {
            return in_array($booking->status, [Booking::STATUS_PENDING, Booking::STATUS_APPROVED], true);
        }
        return false;
    }

    public function approve(User $user, Booking $booking): bool
    {
        return ($user->isProvider() && $user->id === $booking->provider_id) || $user->isAdmin();
    }

    public function decline(User $user, Booking $booking): bool
    {
        return $this->approve($user, $booking);
    }
}
