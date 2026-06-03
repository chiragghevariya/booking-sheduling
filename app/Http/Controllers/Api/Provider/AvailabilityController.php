<?php

namespace App\Http\Controllers\Api\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Availability\StoreAvailabilityRequest;
use App\Http\Requests\Availability\UpdateAvailabilityRequest;
use App\Http\Resources\AvailabilityResource;
use App\Models\Availability;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Provider-scoped CRUD for weekly availability windows.
 * Admins may target any provider via ?provider_id=.
 */
class AvailabilityController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Availability::class);

        $providerId = $this->resolveProviderId($request);

        $items = Availability::query()
            ->where('provider_id', $providerId)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        return AvailabilityResource::collection($items);
    }

    public function store(StoreAvailabilityRequest $request): AvailabilityResource
    {
        $availability = Availability::create([
            ...$request->validated(),
            'provider_id' => $this->resolveProviderId($request),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return new AvailabilityResource($availability);
    }

    public function show(Availability $availability): AvailabilityResource
    {
        $this->authorize('view', $availability);

        return new AvailabilityResource($availability);
    }

    public function update(UpdateAvailabilityRequest $request, Availability $availability): AvailabilityResource
    {
        // Authorization handled by UpdateAvailabilityRequest::authorize().
        $availability->update($request->validated());

        return new AvailabilityResource($availability);
    }

    public function destroy(Request $request, Availability $availability): JsonResponse
    {
        $this->authorize('delete', $availability);

        $availability->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    private function resolveProviderId(Request $request): int
    {
        $user = $request->user();

        if ($user->isAdmin() && $request->filled('provider_id')) {
            return (int) $request->input('provider_id');
        }

        return $user->id;
    }
}
