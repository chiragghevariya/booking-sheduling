<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeviceToken\StoreDeviceTokenRequest;
use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;

class DeviceTokenController extends Controller
{
    /**
     * POST /api/device-token — upsert the (user_id, token) pair.
     *
     * Re-sending the same token simply bumps last_seen_at, so the mobile
     * app can call this every login + session-restore without piling rows.
     */
    public function store(StoreDeviceTokenRequest $request): JsonResponse
    {
        $data = $request->validated();

        DeviceToken::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'token' => $data['token'],
            ],
            [
                'platform' => $data['platform'],
                'last_seen_at' => now(),
            ]
        );

        return response()->json(['message' => 'Device token saved.']);
    }
}
