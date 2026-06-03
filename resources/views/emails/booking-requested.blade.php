@php($appUrl = config('app.url'))
@extends('emails.layouts.main', ['title' => 'New booking request'])

@section('body')
    <h1 style="font-size:20px; font-weight:600; color:#0F172A; margin:0 0 8px 0; letter-spacing:-0.2px;">
        New booking request
    </h1>
    <p style="margin:0 0 16px 0; font-size:14px; color:#475569; line-height:1.6;">
        Hi {{ $booking->provider->name ?? 'there' }} —
        <strong style="color:#0F172A;">{{ $booking->customer->name }}</strong> requested a session.
    </p>

    @include('emails.partials.booking-card', ['booking' => $booking])

    @if ($booking->notes)
        <p style="margin:12px 0 4px 0; font-size:12px; font-weight:600; color:#64748B; text-transform:uppercase; letter-spacing:0.5px;">Notes from {{ $booking->customer->name }}</p>
        <p style="margin:0 0 16px 0; padding:12px 14px; background:#FFFBEB; border:1px solid #FDE68A; border-radius:10px; font-size:13px; color:#7C2D12; line-height:1.5;">
            {{ $booking->notes }}
        </p>
    @endif

    <p style="margin:8px 0 0 0; font-size:13px; color:#475569; line-height:1.6;">
        Contact:
        <a href="mailto:{{ $booking->customer->email }}" style="color:#4F46E5; text-decoration:none;">{{ $booking->customer->email }}</a>
        @if ($booking->customer->phone)
            · {{ $booking->customer->phone }}
        @endif
    </p>

    @include('emails.partials.button', ['url' => rtrim($appUrl, '/') . '/bookings', 'label' => 'Review request'])
@endsection
