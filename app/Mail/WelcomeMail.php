<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ?string $name = null,
        public ?string $actionUrl = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: \App\Models\Setting::getValue('welcome_email_subject', 'Welcome to TENA'),
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: view('emails.welcome', [
                'name' => $this->name,
                'actionUrl' => $this->actionUrl,
            ])->render(),
        );
    }
}
