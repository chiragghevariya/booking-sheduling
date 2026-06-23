<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\UserResource;

/**
 * Lets the authenticated user edit their OWN profile (name, phone, timezone).
 * Providers use this to set the timezone their availability/bookings display in.
 */
class ProfileController extends Controller
{
    /** PATCH /api/profile */
    public function update(UpdateProfileRequest $request): UserResource
    {
        $user = $request->user();

        // Only validated fields (name/phone/timezone) — role/email/password
        // are not in the rules, so they cannot be changed here.
        $user->fill($request->validated())->save();

        return new UserResource($user);
    }
}
