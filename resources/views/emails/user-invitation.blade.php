@php
    $primaryColor = '#1b1b1b';
    $accentColor = '#FFD300';
    $businessName = 'Tena';
    $businessAddress = 'Nairobi, Kenya';
    $baseUrl = config('app.url', 'https://tena.host');
    $footerImageUrl = $baseUrl . '/Email/Tena-email-footer.png';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>You've been invited to Tena</title>
    <style>
        p, td, div, li { word-wrap:break-word; overflow-wrap:break-word; word-break:normal; }
    </style>
</head>
<body style="margin:0;padding:0;background-color:{{ $primaryColor }};font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:{{ $primaryColor }};padding:40px 20px;">
        <tr><td align="center">
            <table width="100%" cellpadding="0" cellspacing="0" style="max-width:700px;background-color:#fff;border-radius:16px;overflow:hidden;">
                <tr><td align="center" style="padding:32px 40px;background-color:{{ $accentColor }}">
                    <img src="{{ $baseUrl }}/legacy/assets/Tena-logo-square.jpg" alt="{{ $businessName }}" height="48" style="display:block;border-radius:12px;" />
                </td></tr>
                <tr><td style="padding:32px 40px 16px">
                    <h1 style="margin:0;font-size:22px;font-weight:700;color:{{ $primaryColor }}">You've been invited to join {{ $businessName }}</h1>
                </td></tr>
                <tr><td style="padding:0 40px 32px;font-size:15px;line-height:1.7;color:#333;max-width:600px">
                    <p style="margin:0 0 16px">Hello {{ $name ?? 'there' }},</p>
                    <p style="margin:0 0 16px">
                        @if($invitedBy)
                            <strong>{{ $invitedBy }}</strong> has invited you to join {{ $businessName }} as a
                            <strong>{{ ucfirst($role ?? 'user') }}</strong>.
                        @else
                            You've been invited to join {{ $businessName }} as a <strong>{{ ucfirst($role ?? 'user') }}</strong>.
                        @endif
                    </p>
                    <p style="margin:0 0 16px">Click the button below to set up your password and get started:</p>
                    <p>
                        <a href="{{ $actionUrl }}" style="display:inline-block;padding:14px 32px;background-color:{{ $accentColor }};color:#000;font-weight:700;font-size:14px;text-decoration:none;border-radius:10px">Set Up Your Password</a>
                    </p>
                    <p style="margin:24px 0 0;font-size:13px;color:#888">
                        This invitation link will expire in 60 minutes. If you didn't expect this email, you can safely ignore it.
                    </p>
                </td></tr>
            </table>
            <table width="100%" cellpadding="0" cellspacing="0" style="max-width:700px;margin-top:16px;">
                <tr><td align="center" style="padding:0 0 8px;"><img src="{{ $footerImageUrl }}" alt="" width="700" style="display:block;width:100%;max-width:700px;height:auto;border-radius:12px;" /></td></tr>
                <tr><td align="center" style="padding:8px 20px 0"><p style="margin:0;font-size:11px;color:#aaa">{{ $businessName }} &middot; {{ $businessAddress }}</p></td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
