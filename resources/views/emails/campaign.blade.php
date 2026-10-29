{{-- A campaign email: the host's message in a simple branded card. Inline styles only (email clients). --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $subject }}</title>
</head>
<body style="margin:0;padding:0;background:#f3efe9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#17171b;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3efe9;padding:28px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:24px;overflow:hidden;">
                    <tr>
                        <td style="background:{{ $accent }};color:{{ $accentInk }};padding:22px 28px;">
                            @if($logoUrl)
                                <img src="{{ $logoUrl }}" alt="{{ $propertyName }}" height="36" style="display:block;max-height:36px;border:0;">
                            @else
                                <span style="font-size:17px;font-weight:700;letter-spacing:-.01em;">{{ $propertyName }}</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:30px 28px 8px;">
                            <h1 style="margin:0 0 14px;font-size:24px;line-height:1.2;font-weight:600;letter-spacing:-.02em;">{{ $subject }}</h1>
                            <div style="font-size:16px;line-height:1.6;color:#33333a;">{!! $body !!}</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:22px 28px 28px;font-size:12px;line-height:1.5;color:#7a7a82;">
                            You're receiving this because you stayed with {{ $propertyName }} and agreed to hear about offers.
                            <a href="{{ $unsubscribeUrl }}" style="color:#7a7a82;">Unsubscribe</a>.
                        </td>
                    </tr>
                </table>
                <p style="font-size:11px;color:#9a9aa2;margin:14px 0 0;">Sent with {{ \App\Support\Brand::name() }}</p>
            </td>
        </tr>
    </table>
    @if($pixel)<img src="{{ $pixel }}" width="1" height="1" alt="" style="display:block;border:0">@endif
</body>
</html>
