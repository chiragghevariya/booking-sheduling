<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Services\SlotAvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class SlotsController extends Controller
{
    /**
     * GET /api/slots?service_id=&from=YYYY-MM-DD&to=YYYY-MM-DD
     *
     * Authenticated users (customer/provider/admin) can query open slots
     * for any active service. The slot service does its own filtering against
     * pending+approved bookings and exceptions.
     */
    public function index(Request $request, SlotAvailabilityService $slots): JsonResponse
    {
        $data = $request->validate([
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $service = Service::with('provider')->findOrFail($data['service_id']);

        // Interpret the requested range in the PROVIDER's timezone so day
        // boundaries line up with how availability is authored and emitted.
        // startOfDay() also normalizes away createFromFormat's "current time".
        $tz = $service->provider->timezone ?: config('app.timezone');

        // Cap the lookahead window to keep responses bounded.
        $from = Carbon::createFromFormat('Y-m-d', $data['from'], $tz)->startOfDay();
        $to = Carbon::createFromFormat('Y-m-d', $data['to'], $tz)->startOfDay();
        if ($from->diffInDays($to) > 60) {
            $to = $from->copy()->addDays(60);
        }

        $result = $slots->slotsFor($service->provider, $service, $from, $to);

        return response()->json([
            'data' => $result,
            'meta' => [
                'service_id' => $service->id,
                'duration_minutes' => $service->duration_minutes,
                'buffer_minutes' => $service->buffer_minutes,
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
        ]);
    }
}
