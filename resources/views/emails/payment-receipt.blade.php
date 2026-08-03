<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt</title>
    <style>
        body { margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: #f8f9fa; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; }
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
        .message { color: #666; font-size: 14px; line-height: 1.6; margin-bottom: 24px; }
        .footer { background: #f8f9fa; padding: 30px; text-align: center; border-top: 1px solid #e9ecef; }
        .footer-text { color: #999; font-size: 12px; margin: 0; }
        .footer-link { color: {{ $primary_color ?? '#000000' }}; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            @if($logo_url)
                <img src="{{ $logo_url }}" alt="{{ $site_name }}" class="logo">
            @endif
            <h1>{{ $site_name ?? 'Tena Host' }}</h1>
        </div>

        <div class="content">
            <p class="greeting">Hello {{ $user_name ?? 'Valued Customer' }},</p>

            <p class="message">
                {{ $custom_heading ?? 'Payment Received!' }}
            </p>

            @if($custom_body)
                <p class="message">{{ $custom_body }}</p>
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
