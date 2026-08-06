<?php

namespace App\Mail;

use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TestPaymentReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $recipientName = 'Test User',
        public float $amount = 6500,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payment Receipt - '.Setting::getValue('site_name', 'Tena Host'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-receipt',
            with: [
                'primary_color' => Setting::getValue('email_primary_color', '#000000'),
                'accent_color' => Setting::getValue('email_accent_color', '#FFD300'),
                'site_name' => Setting::getValue('site_name', 'Tena Host'),
                'logo_url' => Setting::getValue('logo_url', '/legacy/assets/Tena-logo-square.jpg'),
                'business_address' => Setting::getValue('business_address', 'Nairobi, Kenya'),
                'user_name' => $this->recipientName,
                'amount' => $this->amount,
                'receipt_number' => 'TEST_'.strtoupper(uniqid()),
                'transaction_id' => 'TXN_TEST_'.time(),
                'date' => now()->format('M d, Y H:i'),
                'custom_heading' => Setting::getValue('receipt_email_heading', 'Payment Received!'),
                'custom_body' => Setting::getValue('receipt_email_body', ''),
            ],
        );
    }
}
