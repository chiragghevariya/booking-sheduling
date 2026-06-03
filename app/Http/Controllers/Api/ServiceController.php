<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ServiceController extends Controller
{
    /**
     * GET /api/services — list active, bookable services. Public to authed users.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $items = Service::query()
            ->with('provider')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return ServiceResource::collection($items);
    }

    public function show(Service $service): ServiceResource
    {
        return new ServiceResource($service->load('provider'));
    }
}
