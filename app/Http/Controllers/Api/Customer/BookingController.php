<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\RescheduleBookingRequest;
use App\Http\Requests\Booking\StoreBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Service;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;

/**
 * Customer-facing booking endpoints: list/own + request + show + reschedule + cancel.
 * Provider approve/decline lives in Phase 5 under a separate controller.
 */
class BookingController extends Controller
{
    public function __construct(private readonly BookingService $bookings)
    {
    }

    /** Customer's own bookings, newest first. */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Booking::class);

        $items = Booking::query()
            ->where('customer_id', $request->user()->id)
            ->with(['service', 'provider'])
            ->orderByDesc('starts_at')
            ->get();

        return BookingResource::collection($items);
    }

    public function store(StoreBookingRequest $request): JsonResponse
    {
        $service = Service::with('provider')->findOrFail($request->integer('service_id'));

        $booking = $this->bookings->request(
            customer: $request->user(),
            service: $service,
            startsAt: Carbon::parse($request->input('starts_at')),
            extras: [
                'phone' => $request->input('phone'),
                'notes' => $request->input('notes'),
            ],
        );

        return (new BookingResource($booking->load(['service', 'provider'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Booking $booking): BookingResource
    {
        $this->authorize('view', $booking);

        return new BookingResource($booking->load(['service', 'provider', 'customer']));
    }

    /** Reschedule a pending booking; resets approval state. */
    public function update(RescheduleBookingRequest $request, Booking $booking): BookingResource
    {
        $booking = $this->bookings->reschedule(
            $booking,
            Carbon::parse($request->input('starts_at')),
        );

        return new BookingResource($booking);
    }

    public function destroy(Request $request, Booking $booking): BookingResource
    {
        $this->authorize('cancel', $booking);

        $booking = $this->bookings->customerCancel($booking);

        return new BookingResource($booking);
    }
}
