<?php

namespace App\Mail;

use App\Models\Setting;
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
            subject: Setting::getValue('welcome_email_subject', 'Welcome to TENA'),
        );
    }

    public function content(): Content
    {
        $businessName = Setting::getValue('site_name', 'Tena');
        $businessAddress = Setting::getValue('business_address', 'Nairobi, Kenya');

        $replacements = [
            '{{First Name}}' => $this->name ?? 'there',
            '{{Last Name}}' => '',
            '{{Email}}' => '',
            '{{Name}}' => $this->name ?? 'there',
            '{{Login URL}}' => $this->actionUrl ?? '#',
            '{{Business Name}}' => $businessName,
            '{{Business Address}}' => $businessAddress,
        ];

        return new Content(
            htmlString: view('emails.welcome', [
                'name' => $this->name,
                'actionUrl' => $this->actionUrl,
                'resolvedHeading' => $this->resolveVariables(
                    Setting::getValue('welcome_email_heading', ''),
                    $replacements
                ),
                'resolvedBody' => $this->resolveVariables(
                    Setting::getValue('welcome_email_body', ''),
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
            $key = '{{' . trim(strip_tags($matches[1])) . '}}';
            return $replacements[$key] ?? $matches[0];
        }, $content);
    }
}
