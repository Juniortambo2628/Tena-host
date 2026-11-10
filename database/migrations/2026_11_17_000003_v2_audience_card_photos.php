<?php

use App\Models\LandingMedia;
use App\Models\LandingPage;
use App\Models\LandingSection;
use Illuminate\Database\Migrations\Migration;

/**
 * The homepage's "Which one are you?" cards: short-term rentals and local
 * businesses get real photos (a host welcoming a guest, a barista serving a
 * customer), in the same style as the events card, instead of the phone
 * mockup pictures uploaded to those slots. Glen asked for this, so unlike
 * 2026_11_16 it replaces an upload too; the uploaded files stay in storage
 * and can be put back in Admin -> Public Pages.
 */
return new class extends Migration
{
    private const DIR = '/legacy/assets/v2/photos/';

    /** media key => photo, on the v2 homepage's path_cards section */
    private const PHOTOS = [
        'card_0_image' => 'card-rentals.webp',
        'card_1_image' => 'card-business.webp',
    ];

    public function up(): void
    {
        $section = LandingSection::whereHas('page', fn ($q) => $q->where('slug', 'v2'))->where('section_key', 'path_cards')->first();
        if (! $section) {
            return;
        }

        foreach (self::PHOTOS as $mediaKey => $photo) {
            LandingMedia::updateOrCreate(
                ['section_id' => $section->id, 'media_key' => $mediaKey],
                ['original_path' => self::DIR.$photo, 'optimized_path' => null, 'thumbnail_path' => null, 'mime_type' => 'image/webp', 'file_size' => 0, 'crop_data' => null],
            );
        }

        LandingPage::clearCache();
    }

    public function down(): void
    {
        //
    }
};
