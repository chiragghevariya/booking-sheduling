@php($appUrl = config('app.url'))
@extends('emails.layouts.main', ['title' => 'Booking update'])

@section('body')
    <h1 style="font-size:20px; font-weight:600; color:#0F172A; margin:0 0 8px 0; letter-spacing:-0.2px;">
        Booking update
    </h1>
    <p style="margin:0 0 16px 0; font-size:14px; color:#475569; line-height:1.6;">
        Hi {{ $booking->customer->name ?? 'there' }} — unfortunately your request couldn't be confirmed.
    </p>

    @include('emails.partials.booking-card', ['booking' => $booking])

    @if ($booking->decline_reason)
        <p style="margin:14px 0 0 0; padding:12px 14px; background:#FEF2F2; border:1px solid #FECACA; border-radius:10px; font-size:13px; color:#991B1B; line-height:1.5;">
            {{ $booking->decline_reason }}
        </p>
    @endif

    <p style="margin:16px 0 0 0; font-size:13px; color:#475569; line-height:1.6;">
        You can pick another time from your account — most providers have plenty of open slots.
    </p>

    @include('emails.partials.button', ['url' => rtrim($appUrl, '/') . '/book', 'label' => 'Find another time'])
@endsection
