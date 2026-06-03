<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\DeviceToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends booking-related pushes through Expo's hosted push gateway.
 *
 * No queue/job wrapper here for now — call from inside an already-queued
 * mail job or from a controller method that's already fast. If push volume
 * grows, wrap each call in a dispatch() to a queued job and the public API
 * doesn't change.
 *
 * Reference: https://docs.expo.dev/push-notifications/sending-notifications/
 */
class ExpoPushService
{
    private const PUSH_URL = 'https://exp.host/--/api/v2/push/send';

    public function bookingApproved(Booking $booking): void
    {
        $this->sendToCustomer(
            $booking,
            title: 'Booking approved',
            body: $this->bookingLine($booking, 'is confirmed'),
        );
    }

    public function bookingDeclined(Booking $booking): void
    {
        $body = $booking->decline_reason
            ? "Reason: {$booking->decline_reason}"
            : $this->bookingLine($booking, 'was declined');

        $this->sendToCustomer(
            $booking,
            title: 'Booking declined',
            body: $body,
        );
    }

    /**
     * Send to every device token registered against the booking's customer.
     * Silently logs and continues on any failure — push is best-effort.
     */
    private function sendToCustomer(Booking $booking, string $title, string $body): void
    {
        $tokens = DeviceToken::query()
            ->where('user_id', $booking->customer_id)
            ->pluck('token');

        if ($tokens->isEmpty()) {
            return;
        }

        $messages = $tokens->map(fn (string $to) => [
            'to' => $to,
            'sound' => 'default',
            'title' => $title,
            'body' => $body,
            'data' => [
                'booking_id' => $booking->id,
            ],
        ])->all();

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->timeout(10)
                ->post(self::PUSH_URL, $messages);

            if ($response->failed()) {
                Log::warning('Expo push gateway returned non-2xx', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'booking_id' => $booking->id,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Expo push dispatch failed', [
                'error' => $e->getMessage(),
                'booking_id' => $booking->id,
            ]);
        }
    }

    private function bookingLine(Booking $booking, string $verbPhrase): string
    {
        $service = $booking->service?->name ?? 'Your booking';
        $when = $booking->starts_at?->isoFormat('ddd, MMM D · h:mm A') ?? '';

        return trim("{$service} {$verbPhrase}" . ($when ? " — {$when}" : ''));
    }
}
