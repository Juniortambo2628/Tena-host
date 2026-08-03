@php
    $primaryColor = \App\Models\Setting::getValue('email_primary_color', '#000000');
    $accentColor = \App\Models\Setting::getValue('email_accent_color', '#FFD300');
    $businessName = \App\Models\Setting::getValue('site_name', 'Tena');
    $businessAddress = \App\Models\Setting::getValue('business_address', 'Nairobi, Kenya');
    $logoUrl = \App\Models\Setting::getValue('logo_url', '');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Verification Code</title>
</head>
<body style="margin:0;padding:0;background-color:{{ $primaryColor }};font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:{{ $primaryColor }};padding:40px 20px;">
        <tr><td align="center">
            <table width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background-color:#fff;border-radius:16px;overflow:hidden;">
                @if($logoUrl)
                <tr><td align="center" style="padding:32px 40px 0"><img src="{{ $logoUrl }}" alt="{{ $businessName }}" height="48" /></td></tr>
                @endif
                <tr><td style="padding:32px 40px 16px"><h1 style="margin:0;font-size:22px;font-weight:700;color:{{ $primaryColor }}">Your Verification Code</h1></td></tr>
                <tr><td style="padding:0 40px 32px;font-size:15px;line-height:1.7;color:#333">
                    <p style="margin:0 0 16px">Hello,</p>
                    <p style="margin:0 0 24px">Use the following code to complete your verification:</p>
                    <div style="background-color:#f8f8f8;border-radius:12px;padding:20px;text-align:center;margin-bottom:24px">
                        <span style="font-size:32px;font-weight:700;letter-spacing:6px;color:{{ $primaryColor }}">{{ $code }}</span>
                    </div>
                    <p style="margin:0;font-size:13px;color:#888">This code expires in 15 minutes and can only be used once.</p>
                </td></tr>
                <tr><td style="padding:0 40px 16px"><p style="margin:0;font-size:13px;color:#888">If you didn't request this, please ignore this email.</p></td></tr>
            </table>
            <table width="100%" cellpadding="0" cellspacing="0" style="max-width:560px"><tr><td align="center" style="padding:24px 20px 0"><p style="margin:0;font-size:11px;color:#aaa">{{ $businessName }} &middot; {{ $businessAddress }}</p></td></tr></table>
        </td></tr>
    </table>
</body>
</html>
