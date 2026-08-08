@php
    $primaryColor = \App\Models\Setting::getValue('email_primary_color', '#000000');
    $accentColor = \App\Models\Setting::getValue('email_accent_color', '#FFD300');
    $headerBgColor = '#ffdb00';
    $businessName = \App\Models\Setting::getValue('site_name', 'Tena');
    $businessAddress = \App\Models\Setting::getValue('business_address', 'Nairobi, Kenya');
    $logoUrl = \App\Models\Setting::getValue('logo_url', '');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Changed</title>
</head>
<body style="margin:0;padding:0;background-color:{{ $primaryColor }};font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:{{ $primaryColor }};padding:40px 20px;">
        <tr><td align="center">
            <table width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background-color:#fff;border-radius:16px;overflow:hidden;">
                @if($logoUrl)
                <tr><td align="center" style="padding:32px 40px;background-color:{{ $headerBgColor }}"><img src="{{ $logoUrl }}" alt="{{ $businessName }}" height="48" /></td></tr>
                @endif
                <tr><td style="padding:32px 40px 16px"><h1 style="margin:0;font-size:22px;font-weight:700;color:{{ $primaryColor }}">Password Changed</h1></td></tr>
                <tr><td style="padding:0 40px 32px;font-size:15px;line-height:1.7;color:#333">
                    <p style="margin:0 0 16px">Hello {{ $name ?? 'there' }},</p>
                    <p style="margin:0 0 24px">This is a confirmation that your password was recently changed.</p>
                    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f8f8f8;border-radius:12px;margin-bottom:24px">
                        <tr><td style="padding:12px 20px;font-size:13px;color:#888">When</td><td style="padding:12px 20px;font-size:14px;font-weight:600;color:#333">{{ $changedAt ?? 'Just now' }}</td></tr>
                        <tr><td style="padding:12px 20px;font-size:13px;color:#888">IP Address</td><td style="padding:12px 20px;font-size:14px;font-weight:600;color:#333">{{ $ipAddress ?? 'Unknown' }}</td></tr>
                        <tr><td style="padding:12px 20px;font-size:13px;color:#888">Device</td><td style="padding:12px 20px;font-size:14px;font-weight:600;color:#333">{{ $device ?? 'Unknown' }}</td></tr>
                    </table>
                    <p style="margin:0;font-size:14px;color:#888">If this was you, no action needed. If you didn't change your password, please secure your account immediately or contact support.</p>
                </td></tr>
            </table>
            <table width="100%" cellpadding="0" cellspacing="0" style="max-width:560px"><tr><td align="center" style="padding:24px 20px 0"><p style="margin:0;font-size:11px;color:#aaa">{{ $businessName }} &middot; {{ $businessAddress }}</p></td></tr></table>
        </td></tr>
    </table>
</body>
</html>
