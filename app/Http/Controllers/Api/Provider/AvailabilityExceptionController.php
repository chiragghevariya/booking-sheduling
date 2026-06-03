<?php

namespace App\Http\Controllers\Api\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Availability\StoreAvailabilityExceptionRequest;
use App\Http\Requests\Availability\UpdateAvailabilityExceptionRequest;
use App\Http\Resources\AvailabilityExceptionResource;
use App\Models\Availability;
use App\Models\AvailabilityException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Date-specific availability overrides (blocked dates / custom windows).
 */
class AvailabilityExceptionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        if (! $user->can('viewAny', Availability::class)) {
            throw new AuthorizationException();
        }

        $providerId = $this->resolveProviderId($request);

        $items = AvailabilityException::query()
            ->where('provider_id', $providerId)
            ->orderBy('date')
            ->get();

        return AvailabilityExceptionResource::collection($items);
    }

    public function store(StoreAvailabilityExceptionRequest $request): AvailabilityExceptionResource
    {
        $exception = AvailabilityException::create([
            ...$request->validated(),
            'provider_id' => $this->resolveProviderId($request),
        ]);

        return new AvailabilityExceptionResource($exception);
    }

    public function show(Request $request, AvailabilityException $exception): AvailabilityExceptionResource
    {
        $this->authorize('viewException', $exception);

        return new AvailabilityExceptionResource($exception);
    }

    public function update(UpdateAvailabilityExceptionRequest $request, AvailabilityException $exception): AvailabilityExceptionResource
    {
        $exception->update($request->validated());

        return new AvailabilityExceptionResource($exception);
    }

    public function destroy(Request $request, AvailabilityException $exception): JsonResponse
    {
        $this->authorize('deleteException', $exception);

        $exception->delete();

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
