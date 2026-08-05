<?php

namespace App\Mail;

use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WaitlistWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $email,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: Setting::getValue('waitlist_welcome_subject', 'Welcome to the Tena Family!'),
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: view('emails.waitlist-welcome', [
                'firstName' => $this->firstName,
                'lastName' => $this->lastName,
                'email' => $this->email,
            ])->render(),
        );
    }
}
