<?php

namespace App\Mail;

use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactEnquiryMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $senderName,
        public string $senderEmail,
        public string $subjectLine,
        public string $messageBody,
    ) {}

    public function envelope(): Envelope
    {
        $businessName = Setting::getValue('site_name', 'Tena');

        return new Envelope(
            subject: '[Contact] '.$this->subjectLine,
            replyTo: [new Address($this->senderEmail, $this->senderName)],
            tags: ['contact', 'enquiry'],
            metadata: [
                'business_name' => $businessName,
            ],
        );
    }

    public function content(): Content
    {
        $replacements = [
            '{{Name}}' => $this->senderName,
            '{{Email}}' => $this->senderEmail,
            '{{Subject}}' => $this->subjectLine,
            '{{Message}}' => nl2br(e($this->messageBody)),
            '{{Business Name}}' => Setting::getValue('site_name', 'Tena'),
        ];

        $customHeading = Setting::getValue('contact_enquiry_heading', 'New contact enquiry');
        $customBody = Setting::getValue('contact_enquiry_body', '');

        return new Content(
            htmlString: view('emails.contact-enquiry', [
                'senderName' => $this->senderName,
                'senderEmail' => $this->senderEmail,
                'subjectLine' => $this->subjectLine,
                'messageBody' => $this->messageBody,
                'resolvedHeading' => $this->resolveVariables($customHeading, $replacements),
                'resolvedBody' => $this->resolveVariables($customBody, $replacements),
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
