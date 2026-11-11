{{-- A property transfer update to an owner (TransferUpdateMail), in TenaFi's look like the other account emails. Inline styles only. --}}
@php
    $primaryColor = \App\Models\Setting::getValue('email_primary_color', '#000000');
    $headerBgColor = \App\Support\Brand::YELLOW;
    $businessName = \App\Support\Brand::name();
    $businessAddress = \App\Models\Setting::getValue('business_address', 'Nairobi, Kenya');
    $logoUrl = \App\Support\Brand::emailLogoUrl();
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
            <table width="100%" cellpadding="0" cellspacing="0" style="max-width:640px;background-color:#fff;border-radius:16px;overflow:hidden;">
                @if($logoUrl)
                <tr><td align="center" style="padding:32px 40px;background-color:{{ $headerBgColor }}"><img src="{{ $logoUrl }}" alt="{{ $businessName }}" height="48" /></td></tr>
                @endif
                <tr><td style="padding:32px 40px 8px"><h1 style="margin:0;font-size:22px;font-weight:700;color:{{ $primaryColor }}">{{ $heading }}</h1></td></tr>
                <tr><td style="padding:8px 40px 32px;font-size:15px;line-height:1.7;color:#333">
                    <p style="margin:0 0 16px">Hello {{ $name ?: 'there' }},</p>
                    @foreach($lines as $line)
                        <p style="margin:0 0 16px">{{ $line }}</p>
                    @endforeach
                    @if($button)
                        <p style="margin:24px 0"><a href="{{ $button['url'] }}" style="display:inline-block;background:{{ $primaryColor }};color:#fff;text-decoration:none;font-weight:600;padding:12px 22px;border-radius:999px;">{{ $button['label'] }}</a></p>
                    @endif
                    @if($cancel)
                        <p style="margin:24px 0 0;padding:16px;background:#fff7d6;border-radius:12px;font-size:14px;color:#333">
                            <strong>Wasn't you?</strong> <a href="{{ $cancel['url'] }}" style="color:#000;font-weight:600">{{ $cancel['label'] }}</a>. You don't need to sign in. Then change your password.
                        </p>
                    @endif
                </td></tr>
            </table>
            <table width="100%" cellpadding="0" cellspacing="0" style="max-width:640px"><tr><td align="center" style="padding:24px 20px 0"><p style="margin:0;font-size:12px;color:#aaa">{{ $businessName }} &middot; {{ $businessAddress }}</p></td></tr></table>
        </td></tr>
    </table>
</body>
</html>
