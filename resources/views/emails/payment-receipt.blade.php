@php
    $baseUrl = config('app.url', 'https://tena.host');
    $logoUrl = $logo_url ?? '';
    if ($logoUrl && !str_starts_with($logoUrl, 'http')) {
        $logoUrl = $baseUrl . '/' . ltrim($logoUrl, '/');
    }
    $footerImageUrl = $baseUrl . '/Email/Tena-email-footer.png';
    $headerBgColor = '#ffdb00';
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt</title>
    <!--[if mso]>
    <style>table,td,p,a,span{font-family:Arial,sans-serif !important;}</style>
    <![endif]-->
    <style>
        body { margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: #f8f9fa; }
        .container { max-width: 700px; margin: 0 auto; background: #ffffff; }
        .header { background-color: {{ $primary_color ?? '#000000' }}; padding: 40px 30px; text-align: center; }
        .logo { max-width: 120px; height: auto; margin-bottom: 20px; }
        .header h1 { color: {{ $accent_color ?? '#FFD300' }}; font-size: 24px; margin: 0; font-weight: 700; }
        .content { padding: 40px 30px; }
        .greeting { font-size: 18px; color: #333; margin-bottom: 20px; }
        .receipt-box { background: #f8f9fa; border-radius: 12px; padding: 24px; margin: 24px 0; }
        .receipt-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #e9ecef; }
        .receipt-row:last-child { border-bottom: none; }
        .receipt-label { color: #666; font-size: 14px; }
        .receipt-value { color: #333; font-size: 14px; font-weight: 600; }
        .amount-highlight { font-size: 28px; color: {{ $primary_color ?? '#000000' }}; font-weight: 700; text-align: center; margin: 24px 0; }
        .message { color: #666; font-size: 14px; line-height: 1.6; margin-bottom: 24px; word-wrap: break-word; overflow-wrap: break-word; word-break: normal; }
        .footer-banner { padding: 0 30px 16px; text-align: center; }
        .footer-banner img { width: 100%; max-width: 540px; height: auto; border-radius: 12px; }
        .footer { background: #f8f9fa; padding: 30px; text-align: center; border-top: 1px solid #e9ecef; }
        .footer-text { color: #999; font-size: 12px; margin: 0; }
        .footer-link { color: {{ $primary_color ?? '#000000' }}; text-decoration: none; }
        p, td, div, li { word-wrap: break-word; overflow-wrap: break-word; word-break: normal; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header" style="background-color: {{ $headerBgColor }};">
            @if($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $site_name }}" class="logo" style="display:block;margin:0 auto 20px;">
            @endif
            <h1>{{ $site_name ?? 'Tena Host' }}</h1>
        </div>

        <div class="content">
            @php
                $businessName = $site_name ?? 'Tena Host';
                $replacements = [
                    '{{Name}}' => $user_name ?? 'Valued Customer',
                    '{{First Name}}' => $user_name ?? 'Valued Customer',
                    '{{Amount}}' => number_format($amount, 2),
                    '{{Transaction ID}}' => $transaction_id ?? '',
                    '{{Date}}' => $date ?? now()->format('M d, Y H:i'),
                    '{{Plan Name}}' => 'Host Plan',
                    '{{Business Name}}' => $businessName,
                ];
                $custom_body_resolved = str_replace(array_keys($replacements), array_values($replacements), $custom_body ?? '');
                $custom_heading_resolved = str_replace(array_keys($replacements), array_values($replacements), $custom_heading ?? 'Payment Received!');
            @endphp
            <p class="greeting">Hello {{ $user_name ?? 'Valued Customer' }},</p>

            <p class="message">
                {{ $custom_heading_resolved }}
            </p>

            @if($custom_body_resolved)
                {!! $custom_body_resolved !!}
            @endif

            <div class="receipt-box">
                <div class="receipt-row">
                    <span class="receipt-label">Amount Paid</span>
                    <span class="receipt-value">KES {{ number_format($amount, 2) }}</span>
                </div>
                @if($receipt_number)
                <div class="receipt-row">
                    <span class="receipt-label">M-Pesa Receipt</span>
                    <span class="receipt-value">{{ $receipt_number }}</span>
                </div>
                @endif
                @if($transaction_id)
                <div class="receipt-row">
                    <span class="receipt-label">Transaction ID</span>
                    <span class="receipt-value">{{ $transaction_id }}</span>
                </div>
                @endif
                <div class="receipt-row">
                    <span class="receipt-label">Date</span>
                    <span class="receipt-value">{{ $date ?? now()->format('M d, Y H:i') }}</span>
                </div>
                <div class="receipt-row">
                    <span class="receipt-label">Status</span>
                    <span class="receipt-value" style="color: #22c55e;">Completed</span>
                </div>
            </div>

            <p class="message">
                Thank you for your payment. Your subscription has been activated and you can now access all features of the platform.
            </p>

            <p class="message">
                If you have any questions about this transaction, please contact our support team at <a href="mailto:billing@tena.host" class="footer-link">billing@tena.host</a>.
            </p>
        </div>

        <div class="footer-banner">
            <img src="{{ $footerImageUrl }}" alt="" />
        </div>

        <div class="footer">
            <p class="footer-text">
                {{ $site_name ?? 'Tena Host' }} &copy; {{ date('Y') }}. All rights reserved.
            </p>
            @if($business_address)
                <p class="footer-text">{{ $business_address }}</p>
            @endif
        </div>
    </div>
</body>
</html>
