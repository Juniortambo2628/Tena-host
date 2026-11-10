<?php

use App\Models\LandingMedia;
use App\Models\LandingPage;
use App\Models\LandingSection;
use App\Models\Setting;

function trustedSection(string $slug): LandingSection
{
    return LandingSection::whereHas('page', fn ($q) => $q->where('slug', $slug))->where('section_key', 'partners')->firstOrFail();
}

it('copies each client’s logo from the previous homepage to the v2 Trusted by, by name', function () {
    $home = trustedSection('home');
    $v2 = trustedSection('v2');
    LandingMedia::where('section_id', $v2->id)->where('media_key', 'like', '%_mark')->delete();
    $names = $v2->contents()->where('content_key', 'like', 'partners.%.name')->orderBy('content_key')->pluck('value')->values();

    // On the old homepage: a wide logo for the first client, a tall WiFi page screenshot for the second.
    LandingMedia::updateOrCreate(['section_id' => $home->id, 'media_key' => 'partner_0_logo'], ['original_path' => '/storage/logo-0.png', 'mime_type' => 'image/png', 'file_size' => 1, 'width' => 520, 'height' => 314]);
    LandingMedia::updateOrCreate(['section_id' => $home->id, 'media_key' => 'partner_1_logo'], ['original_path' => '/storage/shot-1.png', 'mime_type' => 'image/png', 'file_size' => 1, 'width' => 600, 'height' => 1200]);
    // A v2 client that already has its own logo keeps it.
    LandingMedia::create(['section_id' => $v2->id, 'media_key' => 'partner_2_mark', 'original_path' => '/storage/own-2.png', 'mime_type' => 'image/png', 'file_size' => 1]);

    (require database_path('migrations/2026_11_17_000002_v2_trusted_by_logos.php'))->up();

    $mark = fn (int $i) => LandingMedia::where('section_id', $v2->id)->where('media_key', "partner_{$i}_mark")->value('original_path');
    expect($names)->not->toBeEmpty()
        ->and($mark(0))->toBe('/storage/logo-0.png')
        ->and($mark(1))->toBeNull()
        ->and($mark(2))->toBe('/storage/own-2.png');
});

it('sends the v2 homepage each client’s WiFi page design and logo', function () {
    $v2 = trustedSection('v2');
    LandingMedia::updateOrCreate(['section_id' => $v2->id, 'media_key' => 'partner_0_logo'], ['original_path' => '/storage/splash-0.jpg', 'mime_type' => 'image/jpeg', 'file_size' => 1, 'width' => 600, 'height' => 1200]);
    LandingMedia::updateOrCreate(['section_id' => $v2->id, 'media_key' => 'partner_0_mark'], ['original_path' => '/storage/logo-0.png', 'mime_type' => 'image/png', 'file_size' => 1]);
    LandingPage::clearCache();
    Setting::setValue('v2_live', '1');

    $this->get('/')->assertOk()->assertInertia(fn ($page) => $page
        ->component('Public/V2/Home')
        ->where('sections', fn ($sections) => ($s = collect($sections)->firstWhere('section_key', 'partners'))
            && $s['media']['partner_0_logo'] === '/storage/splash-0.jpg'
            && $s['media']['partner_0_mark'] === '/storage/logo-0.png'));
});
