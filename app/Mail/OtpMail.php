<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $code,
        public ?string $heading = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->heading ?: 'Your TENA Verification Code',
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: view('emails.otp', [
                'code' => $this->code,
                'heading' => $this->heading,
            ])->render(),
        );
    }
}
