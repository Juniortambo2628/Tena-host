<?php

use App\Models\LandingMedia;
use App\Models\LandingPage;
use App\Models\LandingSection;
use Illuminate\Database\Migrations\Migration;

/**
 * Real TenaFi photos and client WiFi pages (public/legacy/assets/v2/photos)
 * in the v2 pages' picture slots. A slot an admin already filled with their
 * own upload (/storage/...) is left alone; bundled /legacy pictures are replaced.
 */
return new class extends Migration
{
    private const DIR = '/legacy/assets/v2/photos/';

    /** page slug => section key => media key => photo */
    private const PHOTOS = [
        'v2' => [
            'hero' => ['main_image' => 'home-hero.webp'],
            'partners' => [
                'partner_1_logo' => 'client-zero-sips.webp',
                'partner_2_logo' => 'client-georgiolani.webp',
                'partner_3_logo' => 'client-stay-awhile.webp',
            ],
            'path_cards' => ['card_0_image' => 'rentals-wifi-tent.webp', 'card_1_image' => 'cafe-wifi-page.webp'],
            'detailed_features__home' => [
                'section_0_image' => 'wifi-device-phone.webp',
                'section_1_image' => 'cafe-review-request.webp',
                'section_2_image' => 'automated-messages.webp',
                'section_3_image' => 'guest-guide.webp',
            ],
            'credibility' => ['main_image' => 'superhost-stats.webp'],
            'cta_banner__founding' => ['image' => 'city-night-tent.webp'],
        ],
        'v2-hosts' => [
            'hero' => ['main_image' => 'rentals-apartment.webp'],
            'how_it_works' => ['chapter_shot' => 'client-stay-awhile.webp'],
            'credibility' => ['main_image' => 'superhost-stats.webp'],
            'cta_banner__founding' => ['image' => 'rentals-wifi-tent.webp'],
        ],
        'v2-business' => [
            'hero' => ['main_image' => 'cafe-wifi-page.webp'],
            'how_it_works' => ['chapter_shot' => 'client-zero-sips.webp'],
            'credibility' => ['main_image' => 'superhost-stats.webp'],
            'cta_banner__founding' => ['image' => 'barista-wifi.webp'],
        ],
    ];

    public function up(): void
    {
        foreach (self::PHOTOS as $slug => $sections) {
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

    public function down(): void
    {
        // These slots were copies of the live pages' bundled pictures; removing ours shows the page's fallbacks.
        LandingMedia::where('original_path', 'like', self::DIR.'%')->delete();
        LandingPage::clearCache();
    }
};
