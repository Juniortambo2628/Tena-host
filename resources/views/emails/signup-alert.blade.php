@php
    $primaryColor = \App\Models\Setting::getValue('email_primary_color', '#000000');
    $accentColor = \App\Models\Setting::getValue('email_accent_color', '#FFD300');
    $businessName = \App\Models\Setting::getValue('site_name', 'TenaFi');
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
            <table width="100%" cellpadding="0" cellspacing="0" style="max-width:700px;background-color:#fff;border-radius:16px;overflow:hidden;">
                <tr><td style="padding:32px 40px 8px">
                    <h1 style="margin:0;font-size:20px;font-weight:700;color:{{ $primaryColor }}">{{ $heading }}</h1>
                    <p style="margin:8px 0 0;font-size:13px;color:#888">Submitted on the {{ $businessName }} website.</p>
                </td></tr>
                <tr><td style="padding:16px 40px 8px">
                    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f8f8f8;border-radius:12px">
                        @foreach($rows as $label => $value)
                        <tr>
                            <td style="padding:10px 20px;font-size:13px;color:#888;border-bottom:1px solid #eee;width:38%">{{ $label }}</td>
                            <td style="padding:10px 20px;font-size:14px;font-weight:600;color:#333;border-bottom:1px solid #eee">{{ $value }}</td>
                        </tr>
                        @endforeach
                    </table>
                </td></tr>
                @if($consentText)
                <tr><td style="padding:8px 40px;font-size:12px;line-height:1.6;color:#888">
                    Consent{{ $consentedAt ? ' ('.$consentedAt.')' : '' }}: &ldquo;{{ $consentText }}&rdquo;
                </td></tr>
                @endif
                <tr><td style="padding:16px 40px 32px">
                    <a href="{{ $adminUrl }}" style="display:inline-block;padding:12px 24px;background-color:{{ $accentColor }};color:#000;font-weight:700;font-size:14px;text-decoration:none;border-radius:10px">Open in admin</a>
                    @if($phoneLink)
                    <a href="{{ $phoneLink }}" style="display:inline-block;margin-left:8px;padding:12px 24px;border:1px solid #ddd;color:#333;font-weight:600;font-size:14px;text-decoration:none;border-radius:10px">WhatsApp them</a>
                    @endif
                </td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
