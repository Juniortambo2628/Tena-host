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

    $resolvedHeading = $resolvedHeading ?? '';
    $resolvedBody = $resolvedBody ?? '';
    $hasCustomBody = filled(trim(strip_tags($resolvedBody)));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $resolvedHeading ?: 'New contact enquiry' }}</title>
    <!--[if mso]>
    <style>table,td,p,a,span{font-family:Arial,sans-serif !important;}</style>
    <![endif]-->
    <style>
        p, td, div, li { word-wrap:break-word; overflow-wrap:break-word; word-break:normal; }
    </style>
</head>
<body style="margin:0;padding:0;background-color:{{ $primaryColor }};font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:{{ $primaryColor }};padding:40px 20px;">
        <tr><td align="center">
            <table width="100%" cellpadding="0" cellspacing="0" style="max-width:700px;background-color:#fff;border-radius:16px;overflow:hidden;">
                @if($logoUrl)
                <tr><td align="center" style="padding:32px 40px;background-color:{{ $headerBgColor }}"><img src="{{ $logoUrl }}" alt="{{ $businessName }}" height="48" style="display:block;" /></td></tr>
                @endif
                <tr><td style="padding:32px 40px 8px">
                    <h1 style="margin:0;font-size:22px;font-weight:700;color:{{ $primaryColor }}">{{ $resolvedHeading ?: 'New contact enquiry' }}</h1>
                    <p style="margin:8px 0 0;font-size:13px;color:#888">A visitor just sent a message from the {{ $businessName }} website.</p>
                </td></tr>
                <tr><td style="padding:16px 40px 8px">
                    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f8f8f8;border-radius:12px">
                        <tr>
                            <td style="padding:12px 20px;font-size:13px;color:#888;border-bottom:1px solid #eee;width:35%">From</td>
                            <td style="padding:12px 20px;font-size:14px;font-weight:600;color:#333;border-bottom:1px solid #eee">{{ $senderName }}</td>
                        </tr>
                        <tr>
                            <td style="padding:12px 20px;font-size:13px;color:#888;border-bottom:1px solid #eee">Email</td>
                            <td style="padding:12px 20px;font-size:14px;font-weight:600;color:#333;border-bottom:1px solid #eee"><a href="mailto:{{ $senderEmail }}" style="color:{{ $primaryColor }};text-decoration:none">{{ $senderEmail }}</a></td>
                        </tr>
                        <tr>
                            <td style="padding:12px 20px;font-size:13px;color:#888">Subject</td>
                            <td style="padding:12px 20px;font-size:14px;font-weight:600;color:#333">{{ $subjectLine }}</td>
                        </tr>
                    </table>
                </td></tr>
                <tr><td style="padding:16px 40px 32px;font-size:15px;line-height:1.7;color:#333">
                    <div style="word-wrap:break-word;overflow-wrap:break-word;word-break:normal;white-space:normal;mso-word-wrap:break-word;max-width:100%;">
                    @if($hasCustomBody)
                        {!! $resolvedBody !!}
                    @else
                        <p style="margin:0 0 8px;font-size:13px;color:#888;text-transform:uppercase;letter-spacing:0.05em">Message</p>
                        <div style="padding:16px 20px;background:#fafafa;border-left:3px solid {{ $accentColor }};border-radius:8px;color:#333;font-size:15px;line-height:1.7">
                            {!! nl2br(e($messageBody)) !!}
                        </div>
                    @endif
                    </div>
                </td></tr>
                <tr><td style="padding:0 40px 24px">
                    <p style="margin:0"><a href="mailto:{{ $senderEmail }}?subject=Re:%20{{ rawurlencode($subjectLine) }}" style="display:inline-block;padding:14px 32px;background-color:{{ $accentColor }};color:#000;font-weight:700;font-size:14px;text-decoration:none;border-radius:10px">Reply to {{ $senderName }}</a></p>
                </td></tr>
            </table>
            <table width="100%" cellpadding="0" cellspacing="0" style="max-width:700px;margin-top:16px;">
                <tr><td align="center" style="padding:0 0 8px;"><img src="{{ $footerImageUrl }}" alt="{{ $businessName }}" width="600" border="0" style="display:block;width:100%;max-width:600px;height:auto;border-radius:12px;border:0;outline:none;text-decoration:none;margin:0 auto;" /></td></tr>
                <tr><td align="center" style="padding:8px 20px 0"><p style="margin:0;font-size:11px;color:#aaa">{{ $businessName }} &middot; {{ $businessAddress }}</p></td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
