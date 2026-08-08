@php
    $primaryColor = \App\Models\Setting::getValue('email_primary_color', '#000000');
    $accentColor = \App\Models\Setting::getValue('email_accent_color', '#FFD300');
    $headerBgColor = '#ffdb00';
    $businessName = \App\Models\Setting::getValue('site_name', 'Tena');
    $businessAddress = \App\Models\Setting::getValue('business_address', 'Nairobi, Kenya');
    $logoUrl = \App\Models\Setting::getValue('logo_url', '');

    $baseUrl = config('app.url', 'https://tena.host');
    if ($logoUrl && !str_starts_with($logoUrl, 'http')) {
        $logoUrl = $baseUrl . '/' . ltrim($logoUrl, '/');
    }
    $footerImageUrl = $baseUrl . '/Email/Tena-email-footer.png';

    $resolvedBody = $resolvedBody ?? '';
    $resolvedHeading = $resolvedHeading ?? '';

    $varReplacements = [
        '{{First Name}}' => $name ?? 'there',
        '{{Last Name}}' => '',
        '{{Email}}' => '',
        '{{Name}}' => $name ?? 'there',
        '{{Login URL}}' => $actionUrl ?? '#',
        '{{Business Name}}' => $businessName ?? 'Tena',
        '{{Business Address}}' => $businessAddress ?? '',
    ];
    $resolvedHeading = str_replace(array_keys($varReplacements), array_values($varReplacements), html_entity_decode($resolvedHeading));
    $resolvedBody = str_replace(array_keys($varReplacements), array_values($varReplacements), html_entity_decode($resolvedBody));

    $hasCustomBody = filled(trim(strip_tags($resolvedBody)));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $resolvedHeading ?: 'Welcome to Tena' }}</title>
</head>
<body style="margin:0;padding:0;background-color:{{ $primaryColor }};font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:{{ $primaryColor }};padding:40px 20px;">
        <tr><td align="center">
            <table width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background-color:#fff;border-radius:16px;overflow:hidden;">
                @if($logoUrl)
                <tr><td align="center" style="padding:32px 40px;background-color:{{ $headerBgColor }}"><img src="{{ $logoUrl }}" alt="{{ $businessName }}" height="48" style="display:block;" /></td></tr>
                @endif
                <tr><td style="padding:32px 40px 16px"><h1 style="margin:0;font-size:22px;font-weight:700;color:{{ $primaryColor }}">{{ $resolvedHeading ?: 'Welcome to Tena' }}</h1></td></tr>
                <tr><td style="padding:0 40px 32px;font-size:15px;line-height:1.7;color:#333">
                    <p style="margin:0 0 16px">Hello {{ $name ?? 'there' }},</p>
                    @if($hasCustomBody)
                        {!! $resolvedBody !!}
                    @else
                        <p style="margin:0 0 16px">Your account has been created and is ready to use. We're excited to have you on board.</p>
                        <ul style="margin:0 0 16px;padding-left:20px;line-height:1.8">
                            <li>Complete your profile</li>
                            <li>Explore your dashboard</li>
                            <li>Connect your first property</li>
                        </ul>
                    @endif
                    @if(!empty($actionUrl))
                    <p><a href="{{ $actionUrl }}" style="display:inline-block;padding:14px 32px;background-color:{{ $accentColor }};color:#000;font-weight:700;font-size:14px;text-decoration:none;border-radius:10px">Log in to your account</a></p>
                    @endif
                </td></tr>
                <tr><td style="padding:0 40px 16px"><p style="margin:0;font-size:13px;color:#888">If you have any questions, reply to this email or contact our support team.</p></td></tr>
            </table>
            <table width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;margin-top:16px;">
                <tr><td align="center" style="padding:0 0 8px;"><img src="{{ $footerImageUrl }}" alt="" width="560" style="display:block;width:100%;max-width:560px;height:auto;border-radius:12px;" /></td></tr>
                <tr><td align="center" style="padding:8px 20px 0"><p style="margin:0;font-size:11px;color:#aaa">{{ $businessName }} &middot; {{ $businessAddress }}</p></td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
