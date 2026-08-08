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
        $businessName = Setting::getValue('site_name', 'Tena');
        $businessAddress = Setting::getValue('business_address', 'Nairobi, Kenya');

        $replacements = [
            '{{First Name}}' => $this->firstName,
            '{{Last Name}}' => $this->lastName,
            '{{Email}}' => $this->email,
            '{{Business Name}}' => $businessName,
            '{{Business Address}}' => $businessAddress,
        ];

        return new Content(
            htmlString: view('emails.waitlist-welcome', [
                'firstName' => $this->firstName,
                'lastName' => $this->lastName,
                'email' => $this->email,
                'resolvedHeading' => $this->resolveVariables(
                    Setting::getValue('waitlist_welcome_heading', ''),
                    $replacements
                ),
                'resolvedBody' => $this->resolveVariables(
                    Setting::getValue('waitlist_welcome_body', ''),
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
