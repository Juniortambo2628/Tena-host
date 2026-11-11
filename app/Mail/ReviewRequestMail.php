<?php

namespace App\Mail;

use App\Models\Guest;
use App\Support\Brand;
use App\Support\SplashDesign;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

/**
 * The thank-you and review request by email: sent by TenaFi, but in the
 * property's name, logo and colour ("Zero Sips via TenaFi"), with one button
 * to the property's review page through the counted /r/{token} link.
 */
class ReviewRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    /** The default words by email: no "today", as a first send can reach guests from weeks ago. */
    public const DEFAULT_MESSAGE = 'Hi {guest_name}, thank you for staying at {property_name}! If you have a minute, a review would mean a lot to us.';

    public const DEFAULT_BUSINESS_MESSAGE = 'Hi {guest_name}, thank you for visiting {property_name}! How did we do? It takes 30 seconds to share your experience on Google.';

    public function __construct(public Guest $guest, public string $reviewLink) {}

    public function envelope(): Envelope
    {
        $property = $this->guest->property;

        return new Envelope(
            from: new Address((string) config('mail.from.address'), "{$property->name} via ".Brand::name()),
            subject: static::subjectFor($this->guest),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.review-request', with: static::viewData($this->guest, $this->reviewLink));
    }

    public static function subjectFor(Guest $guest): string
    {
        $property = $guest->property;

        return SplashDesign::isBusiness($property)
            ? "Thanks for visiting {$property->name}"
            : "Thank you for staying at {$property->name}";
    }

    /**
     * The words: the property's own review message if it has one, without the
     * link (the button carries it), else the default.
     *
     * @return array<string, mixed>
     */
    public static function viewData(Guest $guest, string $reviewLink): array
    {
        $property = $guest->property;
        $accent = SplashDesign::accent($property);
        $default = SplashDesign::isBusiness($property) ? self::DEFAULT_BUSINESS_MESSAGE : self::DEFAULT_MESSAGE;
        $message = strtr($property->review_message ?: $default, [
            '{guest_name}' => $guest->first_name ?: 'there',
            '{property_name}' => $property->name,
            '{review_link}' => '',
        ]);

        // The look of the site's WiFi page: "society" is deep green, cream and gold,
        // so the cream logo sits on green, as guests saw it; other designs use a
        // white card with the logo on the brand colour.
        $society = ($property->branding_json['template'] ?? null) === 'society';
        $theme = $society
            ? ['page' => '#0B2F24', 'card' => '#0E3A2C', 'header' => '#0E3A2C', 'headerInk' => '#F4EEDC', 'ink' => '#F4EEDC', 'body' => '#E9E3D1', 'muted' => '#B9B4A4', 'rule' => $accent, 'font' => "Georgia,'Times New Roman',serif", 'center' => true]
            : ['page' => '#f3efe9', 'card' => '#ffffff', 'header' => $accent, 'headerInk' => SplashDesign::readableInk($accent), 'ink' => '#17171b', 'body' => '#33333a', 'muted' => '#7a7a82', 'rule' => null, 'font' => null, 'center' => false];

        return [
            'theme' => $theme,
            'subject' => static::subjectFor($guest),
            'intro' => trim(preg_replace('/\s*:\s*$/', '.', trim($message))),
            'buttonLabel' => 'Leave a Google review',
            'reviewLink' => $reviewLink,
            'propertyName' => $property->name,
            'logoUrl' => SplashDesign::imageUrl($property->logo_path),
            'accent' => $accent,
            'accentInk' => SplashDesign::readableInk($accent),
            'business' => SplashDesign::isBusiness($property),
            'unsubscribeUrl' => $guest->review_token
                ? URL::signedRoute('reviews.unsubscribe', ['token' => $guest->review_token])
                : null,
        ];
    }
}
