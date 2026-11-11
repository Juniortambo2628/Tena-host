{{-- A thank-you and review request, in the property's name, logo and colour. Inline styles only (email clients). --}}
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
                            <p style="margin:0;font-size:16px;line-height:1.6;color:#33333a;">{{ $intro }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:22px 28px 6px;">
                            <a href="{{ $reviewLink }}" style="display:inline-block;background:{{ $accent }};color:{{ $accentInk }};text-decoration:none;font-size:16px;font-weight:600;padding:14px 26px;border-radius:999px;">{{ $buttonLabel }}</a>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:22px 28px 28px;font-size:12px;line-height:1.5;color:#7a7a82;">
                            You're receiving this because you {{ $business ? 'visited' : 'stayed with' }} {{ $propertyName }}, connected to the WiFi and agreed to be contacted.
                            @if($unsubscribeUrl)<a href="{{ $unsubscribeUrl }}" style="color:#7a7a82;">Unsubscribe</a>.@endif
                        </td>
                    </tr>
                </table>
                <p style="font-size:12px;color:#9a9aa2;margin:14px 0 0;">Sent for {{ $propertyName }} by {{ \App\Support\Brand::name() }}</p>
            </td>
        </tr>
    </table>
</body>
</html>
