<?php

namespace App\Policies;

use App\Models\Availability;
use App\Models\AvailabilityException;
use App\Models\User;

/**
 * Single policy gating both weekly availability rows and date-specific exceptions.
 * Providers manage their own; admins manage everyone's.
 */
class AvailabilityPolicy
{
    /** List own availability (or any when admin). */
    public function viewAny(User $user): bool
    {
        return $user->isProvider() || $user->isAdmin();
    }

    public function view(User $user, Availability $availability): bool
    {
        return $this->ownsOrAdmin($user, $availability->provider_id);
    }

    public function create(User $user): bool
    {
        return $user->isProvider() || $user->isAdmin();
    }

    public function update(User $user, Availability $availability): bool
    {
        return $this->ownsOrAdmin($user, $availability->provider_id);
    }

    public function delete(User $user, Availability $availability): bool
    {
        return $this->ownsOrAdmin($user, $availability->provider_id);
    }

    /** Same rules applied to exceptions. */
    public function viewException(User $user, AvailabilityException $exception): bool
    {
        return $this->ownsOrAdmin($user, $exception->provider_id);
    }

    public function updateException(User $user, AvailabilityException $exception): bool
    {
        return $this->ownsOrAdmin($user, $exception->provider_id);
    }

    public function deleteException(User $user, AvailabilityException $exception): bool
    {
        return $this->ownsOrAdmin($user, $exception->provider_id);
    }

    private function ownsOrAdmin(User $user, int $providerId): bool
    {
        return $user->isAdmin() || ($user->isProvider() && $user->id === $providerId);
    }
}
