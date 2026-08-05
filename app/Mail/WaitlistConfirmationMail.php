<?php

namespace App\Mail;

use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WaitlistConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $email,
        public string $propertyType,
        public string $units,
        public string $primaryPlatform,
        public string $biggestChallenge,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: Setting::getValue('waitlist_confirmation_subject', "You're on the Tena waitlist!"),
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: view('emails.waitlist-confirmation', [
                'firstName' => $this->firstName,
                'lastName' => $this->lastName,
                'email' => $this->email,
                'propertyType' => $this->propertyType,
                'units' => $this->units,
                'primaryPlatform' => $this->primaryPlatform,
                'biggestChallenge' => $this->biggestChallenge,
            ])->render(),
        );
    }
}
