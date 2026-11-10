<?php

use App\Models\LandingMedia;
use App\Models\LandingPage;
use App\Models\LandingSection;

it('puts real photos on the homepage’s rentals and business cards, replacing uploaded mockups', function () {
    $cards = LandingSection::whereHas('page', fn ($q) => $q->where('slug', 'v2'))->where('section_key', 'path_cards')->firstOrFail();
    // An admin had uploaded phone mockup pictures to the first two cards.
    foreach (['card_0_image', 'card_1_image'] as $key) {
        LandingMedia::updateOrCreate(['section_id' => $cards->id, 'media_key' => $key], ['original_path' => "/storage/landing/{$key}-mockup.jpg", 'mime_type' => 'image/jpeg', 'file_size' => 1]);
    }
    LandingPage::clearCache();

    (require database_path('migrations/2026_11_17_000003_v2_audience_card_photos.php'))->up();

    $this->get('/')->assertOk()->assertInertia(fn ($page) => $page
        ->where('sections', fn ($sections) => ($media = collect($sections)->firstWhere('section_key', 'path_cards')['media'])
            && $media['card_0_image'] === '/legacy/assets/v2/photos/card-rentals.webp'
            && $media['card_1_image'] === '/legacy/assets/v2/photos/card-business.webp'
            && $media['card_2_image'] === '/legacy/assets/v2/photos/card-events.webp'));
});
