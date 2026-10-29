<?php

namespace App\Mail;

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Guest;
use App\Services\CampaignLinks;
use App\Support\Brand;
use App\Support\SplashDesign;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

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
        return new Content(view: 'emails.campaign', with: static::viewData($this->campaign, $this->guest, $this->recipient));
    }

    /**
     * Everything the campaign email template needs. Also used by the studio's
     * preview, with an unsaved campaign and a sample guest.
     *
     * @return array<string, mixed>
     */
    public static function viewData(Campaign $campaign, Guest $guest, ?CampaignRecipient $recipient = null): array
    {
        $property = $guest->property ?? $campaign->property;
        $accent = SplashDesign::accent($property);

        // Same placeholders as WhatsApp/SMS (Messenger::personalize), escaped for HTML.
        $html = strtr(CampaignLinks::emailHtml($campaign->content ?? ''), [
            '%FIRSTNAME%' => e($guest->first_name),
            '%LASTNAME%' => e((string) $guest->last_name),
            '%EMAIL%' => e((string) $guest->email),
            '%PROPERTY%' => e($property?->name ?? ''),
            '{guest_name}' => e($guest->first_name),
            '{property_name}' => e($property?->name ?? ''),
        ]);

        return [
            'body' => $recipient ? CampaignLinks::track($html, $recipient, html: true) : $html,
            'subject' => $campaign->subject ?: $campaign->name,
            'propertyName' => $property?->name ?? Brand::name(),
            'logoUrl' => SplashDesign::imageUrl($property?->logo_path),
            'accent' => $accent,
            'accentInk' => SplashDesign::readableInk($accent),
            'pixel' => $recipient ? CampaignLinks::pixel($recipient) : null,
            'unsubscribeUrl' => $recipient
                ? URL::signedRoute('campaigns.unsubscribe', ['token' => $recipient->token])
                : '#',
        ];
    }
}
