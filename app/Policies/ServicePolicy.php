<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;

class ServicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isProvider() || $user->isAdmin();
    }

    public function view(User $user, Service $service): bool
    {
        return $this->ownsOrAdmin($user, $service->provider_id);
    }

    public function create(User $user): bool
    {
        return $user->isProvider() || $user->isAdmin();
    }

    public function update(User $user, Service $service): bool
    {
        return $this->ownsOrAdmin($user, $service->provider_id);
    }

    public function delete(User $user, Service $service): bool
    {
        return $this->ownsOrAdmin($user, $service->provider_id);
    }

    private function ownsOrAdmin(User $user, int $providerId): bool
    {
        return $user->isAdmin() || ($user->isProvider() && $user->id === $providerId);
    }
}
