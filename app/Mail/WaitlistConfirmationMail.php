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
        $businessName = Setting::getValue('site_name', 'Tena');
        $businessAddress = Setting::getValue('business_address', 'Nairobi, Kenya');

        $replacements = [
            '{{First Name}}' => $this->firstName,
            '{{Last Name}}' => $this->lastName,
            '{{Email}}' => $this->email,
            '{{Property Type}}' => $this->propertyType,
            '{{Units}}' => $this->units,
            '{{Primary Platform}}' => $this->primaryPlatform,
            '{{Biggest Challenge}}' => $this->biggestChallenge,
            '{{Business Name}}' => $businessName,
            '{{Business Address}}' => $businessAddress,
        ];

        return new Content(
            htmlString: view('emails.waitlist-confirmation', [
                'firstName' => $this->firstName,
                'lastName' => $this->lastName,
                'email' => $this->email,
                'propertyType' => $this->propertyType,
                'units' => $this->units,
                'primaryPlatform' => $this->primaryPlatform,
                'biggestChallenge' => $this->biggestChallenge,
                'resolvedHeading' => $this->resolveVariables(
                    Setting::getValue('waitlist_confirmation_heading', ''),
                    $replacements
                ),
                'resolvedBody' => $this->resolveVariables(
                    Setting::getValue('waitlist_confirmation_body', ''),
                    $replacements
                ),
            ])->render(),
        );
    }

    private function resolveVariables(string $content, array $replacements): string
    {
        $content = html_entity_decode($content);
        $content = str_replace(array_keys($replacements), array_values($replacements), $content);

        return preg_replace_callback('/\{\{(.+?)\}\}/s', function ($matches) use ($replacements) {
            $key = '{{'.trim(strip_tags($matches[1])).'}}';

            return $replacements[$key] ?? $matches[0];
        }, $content);
    }
}
