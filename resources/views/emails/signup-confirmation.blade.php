{{--
    "Welcome to TenaFi." after a website sign-up (SignupConfirmationMail; Glen's
    design, Nov 2026). Inline styles and tables only, for email clients; text
    stays at 12px or more.
--}}
@php
    $serif = "Georgia,'Times New Roman',serif";
    $sans = "-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif";
    $ink = '#17171b';
    $body = '#4a4a52';
    $muted = '#7a7a82';
    $line = '#ECE7DA';
    $steps = [
        ['We get in touch', 'A member of the team contacts you to understand your space and agree an installation slot.'],
        ['We install your TenaFi device', 'It plugs into your existing router, so there is nothing for you to set up.'],
        ['Guests sign in', 'They see your brand, join with their name and WhatsApp or email, and give consent.'],
        ['You get a list you own', 'Every login lands on your dashboard, and we run the campaigns that bring guests back.'],
    ];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Welcome to {{ $brandName }}</title>
</head>
<body style="margin:0;padding:0;background:#F3EFE6;font-family:{{ $sans }};color:{{ $ink }};">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F3EFE6;padding:24px 10px;">
<tr><td align="center">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#FDFCF7;border-radius:20px;overflow:hidden;">

    {{-- Logo and line --}}
    <tr><td style="padding:30px 32px 6px;">
        <img src="{{ $logoUrl }}" alt="{{ $brandName }}" height="34" style="display:block;height:34px;border:0;">
        <p style="margin:10px 0 0;padding-left:10px;border-left:3px solid {{ $yellow }};font-size:13px;font-weight:600;color:#0E5A3A;">Africa’s guest relationship platform</p>
    </td></tr>

    {{-- Confirmed --}}
    <tr><td style="padding:22px 32px 26px;">
        <span style="display:inline-block;background:{{ $yellow }};color:{{ $ink }};font-size:12px;font-weight:700;padding:6px 12px;border-radius:999px;">Sign-up confirmed</span>
        <h1 style="margin:16px 0 0;font-family:{{ $serif }};font-size:34px;line-height:1.15;font-weight:700;color:{{ $ink }};">Welcome to {{ $brandName }}.</h1>
        <p style="margin:14px 0 0;font-size:16px;line-height:1.6;color:{{ $body }};">Thanks for signing up{{ $firstName ? ', '.$firstName : '' }}. We turn your existing WiFi into more Google reviews, repeat guests and direct bookings, and we run it for you.</p>
    </td></tr>

    {{-- Their login page, in their own branding --}}
    @if(count($pages))
    <tr><td style="background:{{ $yellow }};padding:26px 16px 24px;" align="center">
        <p style="margin:0;font-size:12px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:{{ $ink }};">Your login page, in your own branding</p>
        <p style="margin:8px 0 18px;font-family:{{ $serif }};font-size:20px;font-weight:700;color:{{ $ink }};">Every page looks like the business it belongs to.</p>
        <table role="presentation" cellpadding="0" cellspacing="0" width="100%"><tr>
            @foreach($pages as $page)
            <td align="center" valign="top" width="{{ floor(100 / count($pages)) }}%" style="padding:0 4px;">
                <div style="border:5px solid {{ $ink }};border-radius:18px;overflow:hidden;background:{{ $ink }};max-width:170px;">
                    <img src="{{ $page['image'] }}" alt="{{ $page['name'] }}’s WiFi page" width="160" style="display:block;width:100%;max-width:160px;height:auto;border:0;">
                </div>
                <p style="margin:10px 0 0;font-family:{{ $serif }};font-size:14px;font-weight:700;color:{{ $ink }};">{{ $page['name'] }}</p>
                @if($page['kind'])<p style="margin:2px 0 0;font-size:12px;color:#5a4a12;">{{ $page['kind'] }}</p>@endif
            </td>
            @endforeach
        </tr></table>
    </td></tr>
    @endif

    {{-- What happens next --}}
    <tr><td style="padding:30px 32px 6px;">
        <p style="margin:0;font-size:13px;font-weight:700;color:#8A6A0A;">What happens next</p>
        <h2 style="margin:6px 0 10px;font-family:{{ $serif }};font-size:24px;line-height:1.25;font-weight:700;color:{{ $ink }};">From a WiFi login to more revenue.</h2>
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
            @foreach($steps as $i => [$title, $text])
            <tr>
                <td valign="top" width="44" style="padding:16px 0;{{ $i ? 'border-top:1px solid '.$line.';' : '' }}">
                    <div style="width:30px;height:30px;line-height:30px;border-radius:50%;background:{{ $yellow }};text-align:center;font-size:13px;font-weight:700;color:{{ $ink }};">{{ $i + 1 }}</div>
                </td>
                <td valign="top" style="padding:16px 0;{{ $i ? 'border-top:1px solid '.$line.';' : '' }}">
                    <p style="margin:0;font-family:{{ $serif }};font-size:17px;font-weight:700;color:{{ $ink }};">{{ $title }}</p>
                    <p style="margin:4px 0 0;font-size:14px;line-height:1.55;color:{{ $body }};">{{ $text }}</p>
                </td>
            </tr>
            @endforeach
        </table>
    </td></tr>

    {{-- On us: the free months (from the live /hosts pricing; hidden when it offers none) --}}
    @if($freeMonths > 0)
    <tr><td style="padding:14px 32px 6px;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:{{ $ink }};border-radius:16px;">
            <tr><td style="padding:22px 22px;">
                <p style="margin:0;font-size:12px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:{{ $yellow }};">On us</p>
                <p style="margin:8px 0 0;font-family:{{ $serif }};font-size:20px;line-height:1.3;font-weight:700;color:#ffffff;">Your first {{ $freeMonths === 1 ? 'month' : $freeMonths.' months' }} of the Basic plan {{ $freeMonths === 1 ? 'is' : 'are' }} free.</p>
                <p style="margin:8px 0 0;font-size:14px;line-height:1.55;color:#d9d7cf;">Branded splash page, guest list and Google review requests by email, from the day you go live.</p>
            </td></tr>
        </table>
    </td></tr>
    @endif

    {{-- What to expect: their kind first --}}
    <tr><td style="padding:28px 32px 6px;">
        <p style="margin:0;font-size:13px;font-weight:700;color:#8A6A0A;">What to expect</p>
        <h2 style="margin:6px 0 14px;font-family:{{ $serif }};font-size:24px;line-height:1.25;font-weight:700;color:{{ $ink }};">The same sign-up, tuned to how you work.</h2>
        @foreach($audiences as $audience)
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:{{ $audience['tint'] }};border-top:5px solid {{ $audience['rule'] }};border-radius:14px;margin:0 0 14px;">
            <tr><td style="padding:18px 20px;">
                <p style="margin:0;font-family:{{ $serif }};font-size:19px;font-weight:700;color:{{ $ink }};">{{ $audience['title'] }}</p>
                <p style="margin:4px 0 10px;font-size:14px;font-weight:700;color:{{ $audience['accent'] }};">{{ $audience['lead'] }}</p>
                @foreach($audience['points'] as $point)
                <p style="margin:6px 0 0;font-size:14px;line-height:1.5;color:{{ $ink }};"><span style="color:{{ $audience['accent'] }};">&bull;</span>&nbsp;&nbsp;{{ $point }}</p>
                @endforeach
            </td></tr>
        </table>
        @endforeach
        <p style="margin:6px 0 0;font-size:14px;line-height:1.55;color:{{ $body }};">Running an event? The same setup captures your attendee list. Just tell us when we get in touch.</p>
    </td></tr>

    {{-- Button and sign-off --}}
    <tr><td align="center" style="padding:26px 32px 6px;">
        <a href="{{ $howUrl }}" style="display:inline-block;background:{{ $yellow }};color:{{ $ink }};text-decoration:none;font-size:15px;font-weight:700;padding:14px 28px;border-radius:12px;">See how {{ $brandName }} works</a>
        <p style="margin:16px 0 0;font-size:13px;color:{{ $muted }};">Questions? Just reply to this email, we read every one.</p>
    </td></tr>
    <tr><td style="padding:24px 32px 30px;">
        <p style="margin:0;font-family:{{ $serif }};font-style:italic;font-size:15px;line-height:1.5;color:{{ $ink }};">“Tena” means again in Swahili. Tena…na Tena…na Tena.</p>
        <p style="margin:12px 0 0;font-size:14px;font-weight:700;color:{{ $ink }};">The {{ $brandName }} Team</p>
    </td></tr>

    {{-- Footer --}}
    <tr><td style="background:{{ $ink }};padding:18px 32px;">
        <p style="margin:0;font-size:12px;line-height:1.6;color:#c9c7bf;">{{ $brandName }} · Africa’s guest relationship platform · <a href="{{ $siteUrl }}" style="color:{{ $yellow }};text-decoration:none;">{{ $siteHost }}</a></p>
        <p style="margin:2px 0 0;font-size:12px;line-height:1.6;color:#c9c7bf;">You received this because you signed up at {{ $siteHost }}.</p>
    </td></tr>

</table>
</td></tr>
</table>
</body>
</html>
