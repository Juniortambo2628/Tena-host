<?php

namespace App\Mail;

use App\Models\MpesaTransaction;
use App\Models\Setting;
use App\Support\Brand;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public MpesaTransaction $transaction,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payment Receipt - '.Brand::name(),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-receipt',
            with: [
                'primary_color' => Setting::getValue('email_primary_color', '#000000'),
                'accent_color' => Setting::getValue('email_accent_color', '#FFD300'),
                'site_name' => Brand::name(),
                'logo_url' => Brand::emailLogoUrl(),
                'business_address' => Setting::getValue('business_address', 'Nairobi, Kenya'),
                'user_name' => $this->transaction->user->name ?? 'Valued Customer',
                'amount' => $this->transaction->Amount,
                'receipt_number' => $this->transaction->MpesaReceiptNumber,
                'transaction_id' => $this->transaction->id,
                'date' => $this->transaction->created_at->format('M d, Y H:i'),
                'custom_heading' => Setting::getValue('receipt_email_heading', 'Payment Received!'),
                'custom_body' => Setting::getValue('receipt_email_body', ''),
            ],
        );
    }
}
