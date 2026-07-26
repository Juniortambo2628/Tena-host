<?php

namespace App\Mail;

use App\Models\Campaign;
use App\Models\Guest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CampaignEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Campaign $campaign,
        public Guest $guest,
    ) {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->campaign->subject ?: $this->campaign->name,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            htmlString: $this->renderContent(),
        );
    }

    /**
     * Render the campaign content with guest personalization.
     */
    protected function renderContent(): string
    {
        $content = $this->campaign->content ?? '';

        $replacements = [
            '%FIRSTNAME%' => e($this->guest->first_name),
            '%LASTNAME%' => e($this->guest->last_name),
            '%EMAIL%' => e($this->guest->email),
            '%PROPERTY%' => e($this->campaign->property?->name ?? ''),
        ];

        return strtr($content, $replacements);
    }
}
