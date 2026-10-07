<?php

namespace App\Mail;

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Guest;
use App\Services\CampaignLinks;
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
        public ?CampaignRecipient $recipient = null,
    ) {}

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

        $html = strtr($content, $replacements);

        if (! $this->recipient) {
            return $html;
        }

        // Tracked links and an open pixel (CampaignLinks).
        return CampaignLinks::track($html, $this->recipient, html: true)
            .'<img src="'.e(CampaignLinks::pixel($this->recipient)).'" width="1" height="1" alt="" style="display:block;border:0" />';
    }
}
