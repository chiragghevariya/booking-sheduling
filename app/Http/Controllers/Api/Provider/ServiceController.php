<?php

namespace App\Http\Controllers\Api\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Service\StoreServiceRequest;
use App\Http\Requests\Service\UpdateServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

/**
 * Provider/admin service management. Providers see/edit only their own.
 */
class ServiceController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Service::class);

        $query = Service::query()->with('provider')->orderBy('name');
        if ($request->user()->isProvider()) {
            $query->where('provider_id', $request->user()->id);
        }

        return ServiceResource::collection($query->get());
    }

    public function store(StoreServiceRequest $request): JsonResponse
    {
        $data = $request->validated();
        $providerId = $request->user()->isAdmin() && $request->filled('provider_id')
            ? (int) $request->input('provider_id')
            : $request->user()->id;

        $service = Service::create([
            ...$data,
            'provider_id' => $providerId,
            'slug' => $this->uniqueSlug($providerId, $data['name']),
            'buffer_minutes' => $data['buffer_minutes'] ?? 0,
            'currency' => $data['currency'] ?? 'USD',
            'is_active' => $request->boolean('is_active', true),
        ]);

        return (new ServiceResource($service->load('provider')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Service $service): ServiceResource
    {
        $this->authorize('view', $service);

        return new ServiceResource($service->load('provider'));
    }

    public function update(UpdateServiceRequest $request, Service $service): ServiceResource
    {
        $data = $request->validated();

        // Regenerate slug if name changed.
        if (isset($data['name']) && $data['name'] !== $service->name) {
            $data['slug'] = $this->uniqueSlug($service->provider_id, $data['name'], $service->id);
        }

        $service->update($data);

        return new ServiceResource($service->fresh('provider'));
    }

    public function destroy(Request $request, Service $service): JsonResponse
    {
        $this->authorize('delete', $service);

        $service->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    /**
     * Generate a slug unique per provider. Appends -2, -3, ... on collision.
     */
    private function uniqueSlug(int $providerId, string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 2;
        while (Service::query()
            ->where('provider_id', $providerId)
            ->where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $slug = $base.'-'.$i++;
        }
        return $slug;
    }
}
