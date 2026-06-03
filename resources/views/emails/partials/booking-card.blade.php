{{-- Reusable booking summary block. Expects $booking in scope. --}}
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
       style="background:#F8FAFC; border:1px solid #E2E8F0; border-radius:12px; margin:8px 0 8px 0;">
    <tr>
        <td style="padding:16px 18px;">
            <p style="margin:0; font-size:11px; font-weight:600; color:#4F46E5; letter-spacing:0.5px; text-transform:uppercase;">
                {{ $booking->service->name ?? 'Booking' }}
            </p>
            <p style="margin:6px 0 0 0; font-size:16px; font-weight:600; color:#0F172A;">
                {{ optional($booking->starts_at)->format('l, F j, Y') }}
            </p>
            <p style="margin:2px 0 0 0; font-size:14px; color:#475569;">
                {{ optional($booking->starts_at)->format('g:i A') }} — {{ optional($booking->ends_at)->format('g:i A') }}
                <span style="color:#94A3B8;">({{ $booking->service->duration_minutes ?? '?' }} min)</span>
            </p>
            <p style="margin:10px 0 0 0; font-size:12px; color:#64748B;">
                with <strong style="color:#0F172A;">{{ $booking->provider->name ?? '' }}</strong>
                · ref <code style="font-family:ui-monospace,monospace; font-size:11px; color:#475569;">{{ $booking->reference }}</code>
            </p>
        </td>
    </tr>
</table>
