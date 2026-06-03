{{--
    Shared email layout. Uses inline styles only — every major email client
    strips or sandboxes <style>, so the design system (indigo accent, light
    surface, rounded corners) is baked into element-level CSS here.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
</head>
<body style="margin:0; padding:0; background:#F8FAFC; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI','Helvetica Neue',Arial,sans-serif; color:#0F172A;">
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#F8FAFC; padding:32px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="560"
                       style="max-width:560px; width:100%; background:#FFFFFF; border:1px solid #E2E8F0; border-radius:14px; overflow:hidden;">
                    <tr>
                        <td style="padding:20px 28px; border-bottom:1px solid #E2E8F0;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="vertical-align:middle;">
                                        <div style="display:inline-block; width:36px; height:36px; background:#EEF2FF; border-radius:10px; text-align:center; line-height:36px; color:#4F46E5; font-weight:700; font-size:18px;">B</div>
                                    </td>
                                    <td style="vertical-align:middle; padding-left:10px;">
                                        <span style="font-size:14px; font-weight:600; color:#0F172A; letter-spacing:-0.2px;">{{ config('app.name') }}</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:28px 28px 12px 28px;">
                            @yield('body')
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:20px 28px 24px 28px; border-top:1px solid #E2E8F0; background:#F8FAFC;">
                            <p style="margin:0; font-size:12px; color:#64748B; line-height:1.5;">
                                You're receiving this because you have an account with {{ config('app.name') }}.<br>
                                Sign in to your dashboard to manage this booking.
                            </p>
                        </td>
                    </tr>
                </table>

                <p style="font-size:11px; color:#94A3B8; margin:16px 0 0 0;">
                    © {{ date('Y') }} {{ config('app.name') }}
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
