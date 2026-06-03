{{-- Inline CTA button. Expects $url and $label in scope. --}}
<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:24px 0 8px 0;">
    <tr>
        <td style="background:#4F46E5; border-radius:10px;">
            <a href="{{ $url }}"
               style="display:inline-block; padding:10px 20px; color:#FFFFFF; text-decoration:none;
                      font-size:14px; font-weight:600; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI','Helvetica Neue',Arial,sans-serif;">
                {{ $label }}
            </a>
        </td>
    </tr>
</table>
