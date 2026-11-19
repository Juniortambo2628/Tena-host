<?php

namespace App\Mail;

use App\Models\LandingPage;
use App\Models\Registration;
use App\Support\Brand;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * "Welcome to TenaFi.": the email someone gets right after signing up on
 * /hosts or /business (Glen's design, Nov 2026). It replaces the old
 * "You're on the list!" waitlist confirmation for website sign-ups.
 *
 * The three WiFi pages are the screenshots uploaded to the homepage's
 * "Trusted by" section (partner_{i}_logo), so the email shows what the site
 * shows; the free months come from the sign-up's own page's live pricing
 * (/hosts or /business; 0 hides them).
 */
class SignupConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Registration $registration) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Welcome to '.Brand::name().': your sign-up is confirmed');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.signup-confirmation', with: static::viewData($this->registration));
    }

    /**
     * Everything the template needs; also used for previews with a sample sign-up.
     *
     * @return array<string, mixed>
     */
    public static function viewData(Registration $registration): array
    {
        // The offer on their own page's pricing (/business for businesses, /hosts
        // otherwise); with no free months there, the box is left out.
        $offer = LandingPage::liveOffer($registration->type === 'business' ? 'business' : 'hosts');
        $audiences = [
            'hosts' => [
                'title' => 'Short-term rental operators',
                'lead' => 'Turn your existing WiFi into higher occupancy.',
                'points' => ['A guest list built from every stay', 'Offers that bring guests back to book direct', 'Repeat bookings without the booking-platform commission'],
                'tint' => '#FBF0C9', 'rule' => Brand::YELLOW, 'accent' => '#8A6A0A',
            ],
            'business' => [
                'title' => 'Business owners',
                'lead' => 'Turn your existing WiFi into more Google reviews.',
                'points' => ['A guest list from everyone who joins your WiFi', 'A Google review request sent after each visit', 'Offers that give customers a reason to return'],
                'tint' => '#DDEBE6', 'rule' => '#0E3A2C', 'accent' => '#0E5A3A',
            ],
        ];
        // Their own kind first.
        if ($registration->type === 'business') {
            $audiences = ['business' => $audiences['business'], 'hosts' => $audiences['hosts']];
        }

        return [
            'firstName' => trim((string) $registration->first_name),
            'logoUrl' => asset(Brand::asset('logo_header_url')),
            'brandName' => Brand::name(),
            'yellow' => Brand::YELLOW,
            'pages' => static::wifiPages(),
            'freeMonths' => $offer['freeMonths'],
            'basePlan' => $offer['basePlan'],
            'audiences' => $audiences,
            'howUrl' => url('/#how'),
            'siteUrl' => url('/'),
            'siteHost' => parse_url(url('/'), PHP_URL_HOST),
        ];
    }

    /**
     * A WiFi page screenshot is taller than it is wide; a logo in the same slot
     * isn't, so it's left out. Pictures we can't measure (elsewhere) are kept.
     */
    private static function looksLikeAPhoneScreen(string $path): bool
    {
        if (str_starts_with($path, 'http')) {
            return true;
        }
        $file = public_path(ltrim(parse_url($path, PHP_URL_PATH) ?? '', '/'));
        $size = is_file($file) ? @getimagesize($file) : false;

        return ! $size || $size[1] > $size[0] * 1.2;
    }

    /**
     * Up to three clients' WiFi pages from "Trusted by": a rental first, then
     * businesses, each with its screenshot, name and kind of place.
     *
     * @return list<array{name: string, kind: string, image: string}>
     */
    public static function wifiPages(): array
    {
        foreach (['home', 'v2'] as $slug) {
            $partners = collect(LandingPage::publicSections($slug))->firstWhere('section_key', 'partners');
            if (! $partners) {
                continue;
            }
            $content = $partners['content'] ?? [];
            $media = $partners['media'] ?? [];
            $pages = [];
            for ($i = 0; isset($content["partners.{$i}.name"]); $i++) {
                $shot = $media["partner_{$i}_logo"] ?? null;
                $shot = is_array($shot) ? ($shot['url'] ?? null) : $shot;
                if (! $shot || ! static::looksLikeAPhoneScreen($shot)) {
                    continue;
                }
                $pages[] = [
                    'name' => trim(strip_tags((string) $content["partners.{$i}.name"])),
                    'kind' => trim(explode('·', strip_tags((string) ($content["partners.{$i}.type_area"] ?? '')))[0]),
                    'image' => str_starts_with($shot, 'http') ? $shot : asset($shot),
                    'hosts' => trim(strip_tags((string) ($content["partners.{$i}.kind"] ?? ''))) === 'hosts',
                ];
            }
            if ($pages) {
                usort($pages, fn ($a, $b) => $b['hosts'] <=> $a['hosts']);

                return array_map(fn ($p) => collect($p)->except('hosts')->all(), array_slice($pages, 0, 3));
            }
        }

        return [];
    }
}
