<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to providers when a customer requests a new booking. Templates land
 * with the rest of the email work in Phase 6 — this stub keeps the rest of the
 * flow exercise-able (queued, with a real recipient).
 */
class BookingRequested extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Booking $booking)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New booking request for '.($this->booking->service->name ?? 'your service'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.booking-requested',
            with: [
                'booking' => $this->booking,
                'customer' => $this->booking->customer,
                'service' => $this->booking->service,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
