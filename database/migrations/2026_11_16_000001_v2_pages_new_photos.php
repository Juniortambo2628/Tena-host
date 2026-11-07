<?php

use App\Models\LandingMedia;
use App\Models\LandingPage;
use App\Models\LandingSection;
use Illuminate\Database\Migrations\Migration;

/**
 * Kevin's new v2 photos (public/legacy/assets/v2/photos) in the v2 pages'
 * picture slots: the homepage hero (wide and phone), the three audience
 * cards, the feature rows and the closing banner; each audience page's hero,
 * and its new "card picture" (the link cards to it from the other pages).
 * A slot an admin filled with their own upload (/storage/...) is left alone;
 * bundled /legacy pictures are replaced. Client logos and the Stay Awhile
 * proof keep their real photos.
 */
return new class extends Migration
{
    private const DIR = '/legacy/assets/v2/photos/';

    /** page slug => section key => media key => photo */
    private const PHOTOS = [
        'v2' => [
            'hero' => ['main_image' => 'home-hero-guest-sofa.webp', 'phone_image' => 'home-hero-phone.webp'],
            'path_cards' => [
                'card_0_image' => 'card-rentals.webp',
                'card_1_image' => 'card-business.webp',
                'card_2_image' => 'card-events.webp',
            ],
            'detailed_features__home' => [
                'section_0_image' => 'feature-wifi-reception.webp',
                'section_1_image' => 'feature-reviews-checkout.webp',
                'section_2_image' => 'feature-campaigns-host-desk.webp',
                'section_3_image' => 'feature-extras-airport-pickup.webp',
                'section_4_image' => 'feature-events-expo.webp',
            ],
            'cta_banner__founding' => ['image' => 'cta-rooftop-dusk.webp'],
        ],
        'v2-hosts' => [
            'hero' => ['main_image' => 'hosts-hero-keys.webp', 'card_image' => 'hosts-card-bed.webp'],
        ],
        'v2-business' => [
            'hero' => ['main_image' => 'business-hero-salon.webp', 'card_image' => 'card-business.webp'],
        ],
        'v2-events' => [
            'hero' => ['main_image' => 'events-hero-expo.webp', 'card_image' => 'card-events.webp'],
        ],
    ];

    /** The photos these slots had before (2026_11_14_000001_v2_pages_photos), for down(). */
    private const PREVIOUS = [
        'v2' => [
            'hero' => ['main_image' => 'home-hero.webp'],
            'path_cards' => ['card_0_image' => 'rentals-wifi-tent.webp', 'card_1_image' => 'cafe-wifi-page.webp'],
            'detailed_features__home' => [
                'section_0_image' => 'wifi-device-phone.webp',
                'section_1_image' => 'cafe-review-request.webp',
                'section_2_image' => 'automated-messages.webp',
                'section_3_image' => 'guest-guide.webp',
            ],
            'cta_banner__founding' => ['image' => 'city-night-tent.webp'],
        ],
        'v2-hosts' => ['hero' => ['main_image' => 'rentals-apartment.webp']],
        'v2-business' => ['hero' => ['main_image' => 'cafe-wifi-page.webp']],
    ];

    public function up(): void
    {
        $this->place(self::PHOTOS);
    }

    public function down(): void
    {
        // Our photos out; the earlier bundled photos back where they were.
        $ours = collect(self::PHOTOS)->flatten()->unique()->map(fn ($p) => self::DIR.$p)->all();
        LandingMedia::whereIn('original_path', $ours)->delete();
        $this->place(self::PREVIOUS);
    }

    /** @param array<string, array<string, array<string, string>>> $photos */
    private function place(array $photos): void
    {
        foreach ($photos as $slug => $sections) {
            foreach ($sections as $key => $slots) {
                $section = LandingSection::whereHas('page', fn ($q) => $q->where('slug', $slug))->where('section_key', $key)->first();
                if (! $section) {
                    continue;
                }
                foreach ($slots as $mediaKey => $photo) {
                    $current = LandingMedia::where('section_id', $section->id)->where('media_key', $mediaKey)->first();
                    if ($current && ! str_starts_with((string) $current->original_path, '/legacy/')) {
                        continue; // the admin's own upload
                    }
                    LandingMedia::updateOrCreate(
                        ['section_id' => $section->id, 'media_key' => $mediaKey],
                        ['original_path' => self::DIR.$photo, 'optimized_path' => null, 'thumbnail_path' => null, 'mime_type' => 'image/webp', 'file_size' => 0, 'crop_data' => null],
                    );
                }
            }
        }
        LandingPage::clearCache();
    }
};
