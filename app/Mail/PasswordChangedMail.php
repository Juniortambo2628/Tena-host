<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ?string $name = null,
        public ?string $changedAt = null,
        public ?string $ipAddress = null,
        public ?string $device = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Password Changed - TENA',
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: view('emails.password-changed', [
                'name' => $this->name,
                'changedAt' => $this->changedAt,
                'ipAddress' => $this->ipAddress,
                'device' => $this->device,
            ])->render(),
        );
    }
}
