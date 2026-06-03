<?php

namespace App\Http\Controllers\Api\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\DeclineBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * Provider/admin booking management. Providers see only their own; admins see all.
 *
 * GET    /api/provider/bookings          list w/ filters (status, from, to, sort)
 * GET    /api/provider/bookings/{id}     show
 * POST   /api/provider/bookings/{id}/approve
 * POST   /api/provider/bookings/{id}/decline   { reason? }
 * DELETE /api/provider/bookings/{id}     provider cancel (approved → cancelled)
 */
class BookingController extends Controller
{
    public function __construct(private readonly BookingService $bookings)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Booking::class);
        abort_unless($request->user()->isProvider() || $request->user()->isAdmin(), 403);

        $validated = $request->validate([
            'status' => ['sometimes', Rule::in([
                Booking::STATUS_PENDING, Booking::STATUS_APPROVED, Booking::STATUS_DECLINED, Booking::STATUS_CANCELLED,
            ])],
            'from' => ['sometimes', 'date_format:Y-m-d'],
            'to' => ['sometimes', 'date_format:Y-m-d', 'after_or_equal:from'],
            'sort' => ['sometimes', Rule::in(['starts_at', '-starts_at', 'created_at', '-created_at'])],
        ]);

        $query = Booking::query()->with(['customer', 'service', 'provider']);

        // Providers are scoped to their own; admins see all.
        if ($request->user()->isProvider()) {
            $query->where('provider_id', $request->user()->id);
        }

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }
        if (! empty($validated['from'])) {
            $query->where('starts_at', '>=', Carbon::createFromFormat('Y-m-d', $validated['from'])->startOfDay());
        }
        if (! empty($validated['to'])) {
            $query->where('starts_at', '<=', Carbon::createFromFormat('Y-m-d', $validated['to'])->endOfDay());
        }

        $sort = $validated['sort'] ?? '-starts_at';
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');
        $query->orderBy($column, $direction);

        return BookingResource::collection($query->get());
    }

    public function show(Booking $booking): BookingResource
    {
        $this->authorize('view', $booking);

        return new BookingResource($booking->load(['customer', 'service', 'provider']));
    }

    public function approve(Request $request, Booking $booking): JsonResponse
    {
        $this->authorize('approve', $booking);

        $result = $this->bookings->approve($booking);

        return response()->json([
            'data' => new BookingResource($result['booking']),
            'meta' => [
                'auto_declined' => $result['autoDeclined']->count(),
                'auto_declined_ids' => $result['autoDeclined']->pluck('id')->all(),
            ],
        ]);
    }

    public function decline(DeclineBookingRequest $request, Booking $booking): BookingResource
    {
        $booking = $this->bookings->decline($booking, $request->input('reason'));

        return new BookingResource($booking);
    }

    public function destroy(Request $request, Booking $booking): BookingResource
    {
        $this->authorize('cancel', $booking);
        // Providers can only cancel approved bookings via this route — pending ones should be declined instead.
        abort_unless($booking->isApproved(), 422, 'Only approved bookings can be cancelled here. Decline pending requests instead.');

        $booking = $this->bookings->providerCancel($booking);

        return new BookingResource($booking);
    }
}
