@php($appUrl = config('app.url'))
@extends('emails.layouts.main', ['title' => 'Booking cancelled'])

@section('body')
    <h1 style="font-size:20px; font-weight:600; color:#0F172A; margin:0 0 8px 0; letter-spacing:-0.2px;">
        Booking cancelled
    </h1>

    @if ($audience === 'provider')
        <p style="margin:0 0 16px 0; font-size:14px; color:#475569; line-height:1.6;">
            Hi {{ $booking->provider->name ?? 'there' }} — {{ $booking->customer->name }} cancelled the following booking. The slot is now open again.
        </p>
    @else
        <p style="margin:0 0 16px 0; font-size:14px; color:#475569; line-height:1.6;">
            Hi {{ $booking->customer->name ?? 'there' }} — your booking has been cancelled. No further action is needed.
        </p>
    @endif

    @include('emails.partials.booking-card', ['booking' => $booking])

    @if ($audience === 'customer')
        @include('emails.partials.button', ['url' => rtrim($appUrl, '/') . '/book', 'label' => 'Book another time'])
    @else
        @include('emails.partials.button', ['url' => rtrim($appUrl, '/') . '/bookings', 'label' => 'Open dashboard'])
    @endif
@endsection
