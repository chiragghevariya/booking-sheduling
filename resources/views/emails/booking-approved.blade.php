@php($appUrl = config('app.url'))
@extends('emails.layouts.main', ['title' => "You're confirmed"])

@section('body')
    <h1 style="font-size:20px; font-weight:600; color:#0F172A; margin:0 0 8px 0; letter-spacing:-0.2px;">
        You're confirmed
    </h1>
    <p style="margin:0 0 16px 0; font-size:14px; color:#475569; line-height:1.6;">
        Hi {{ $booking->customer->name ?? 'there' }} — {{ $booking->provider->name }} approved your booking. See you then!
    </p>

    @include('emails.partials.booking-card', ['booking' => $booking])

    <p style="margin:14px 0 0 0; padding:12px 14px; background:#ECFDF5; border:1px solid #A7F3D0; border-radius:10px; font-size:13px; color:#065F46; line-height:1.5;">
        Add this to your calendar so you don't forget. We'll send a reminder ahead of time.
    </p>

    @include('emails.partials.button', ['url' => rtrim($appUrl, '/') . '/my-bookings', 'label' => 'View my bookings'])
@endsection
