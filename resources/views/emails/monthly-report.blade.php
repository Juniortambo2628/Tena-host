@php
    $primaryColor = \App\Models\Setting::getValue('email_primary_color', '#000000');
    $accentColor = \App\Models\Setting::getValue('email_accent_color', '#FFD300');
    $businessName = \App\Support\Brand::name();
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $heading }}</title>
</head>
<body style="margin:0;padding:0;background-color:{{ $primaryColor }};font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:{{ $primaryColor }};padding:40px 20px;">
        <tr><td align="center">
            <table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background-color:#fff;border-radius:16px;overflow:hidden;">
                <tr><td align="center" style="padding:32px 40px 0"><img src="{{ \App\Support\Brand::emailLogoUrl() }}" alt="{{ $businessName }}" height="40" style="display:block;" /></td></tr>
                <tr><td style="padding:24px 40px 8px">
                    <h1 style="margin:0;font-size:22px;font-weight:700;color:{{ $primaryColor }}">{{ $heading }}</h1>
                    <p style="margin:8px 0 0;font-size:15px;line-height:1.6;color:#555">Hi {{ $name }}, here's what {{ $businessName }} did for you last month.</p>
                </td></tr>
                <tr><td style="padding:16px 40px 8px">
                    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f8f8f8;border-radius:12px">
                        @foreach($rows as $label => $value)
                        <tr>
                            <td style="padding:12px 20px;font-size:14px;color:#666;border-bottom:1px solid #eee">{{ $label }}</td>
                            <td align="right" style="padding:12px 20px;font-size:18px;font-weight:700;color:#111;border-bottom:1px solid #eee">{{ number_format($value) }}</td>
                        </tr>
                        @endforeach
                    </table>
                </td></tr>
                <tr><td style="padding:16px 40px 32px">
                    <a href="{{ $actionUrl }}" style="display:inline-block;padding:12px 24px;background-color:{{ $accentColor }};color:#000;font-weight:700;font-size:14px;text-decoration:none;border-radius:10px">Open your dashboard</a>
                </td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
