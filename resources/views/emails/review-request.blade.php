{{-- A thank-you and review request in the property's name, logo and colours (those of its WiFi page). Inline styles only (email clients). --}}
@php($align = $theme['center'] ? 'center' : 'left')
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $subject }}</title>
</head>
<body style="margin:0;padding:0;background:{{ $theme['page'] }};font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:{{ $theme['ink'] }};">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:{{ $theme['page'] }};padding:28px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:{{ $theme['card'] }};border-radius:24px;overflow:hidden;">
                    <tr>
                        <td align="{{ $align }}" style="background:{{ $theme['header'] }};color:{{ $theme['headerInk'] }};padding:{{ $theme['center'] ? '36px 28px 8px' : '22px 28px' }};text-align:{{ $align }};">
                            @if($logoUrl)
                                <img src="{{ $logoUrl }}" alt="{{ $propertyName }}" height="{{ $theme['center'] ? 56 : 36 }}" style="display:{{ $theme['center'] ? 'inline-block' : 'block' }};max-height:{{ $theme['center'] ? 56 : 36 }}px;max-width:240px;border:0;">
                            @else
                                <span style="font-size:{{ $theme['center'] ? 26 : 17 }}px;font-weight:700;letter-spacing:{{ $theme['center'] ? '.08em' : '-.01em' }};{{ $theme['font'] ? 'font-family:'.$theme['font'].';text-transform:uppercase;' : '' }}">{{ $propertyName }}</span>
                            @endif
                            @if($theme['rule'])
                                <div style="width:96px;height:2px;background:{{ $theme['rule'] }};margin:22px auto 0;line-height:2px;font-size:0;">&nbsp;</div>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:30px 28px 8px;text-align:{{ $align }};">
                            <h1 style="margin:0 0 14px;font-size:{{ $theme['center'] ? 28 : 24 }}px;line-height:1.2;font-weight:600;letter-spacing:-.01em;color:{{ $theme['center'] ? $accent : $theme['ink'] }};{{ $theme['font'] ? 'font-family:'.$theme['font'].';' : '' }}">{{ $subject }}</h1>
                            <p style="margin:0;font-size:16px;line-height:1.6;color:{{ $theme['body'] }};">{{ $intro }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td align="{{ $align }}" style="padding:24px 28px 6px;text-align:{{ $align }};">
                            <a href="{{ $reviewLink }}" style="display:inline-block;background:{{ $accent }};color:{{ $accentInk }};text-decoration:none;font-size:16px;font-weight:600;padding:14px 26px;border-radius:999px;">{{ $buttonLabel }}</a>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px 28px 28px;font-size:12px;line-height:1.5;color:{{ $theme['muted'] }};text-align:{{ $align }};">
                            You're receiving this because you {{ $business ? 'visited' : 'stayed with' }} {{ $propertyName }}, connected to the WiFi and agreed to be contacted.
                            @if($unsubscribeUrl)<a href="{{ $unsubscribeUrl }}" style="color:{{ $theme['muted'] }};">Unsubscribe</a>.@endif
                        </td>
                    </tr>
                </table>
                <p style="font-size:12px;color:{{ $theme['muted'] }};margin:14px 0 0;">Sent for {{ $propertyName }} by {{ \App\Support\Brand::name() }}</p>
            </td>
        </tr>
    </table>
</body>
</html>
