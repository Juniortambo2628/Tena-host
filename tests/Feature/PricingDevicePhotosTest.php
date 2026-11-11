<?php

use App\Models\LandingMedia;
use App\Models\LandingPage;
use App\Models\LandingSection;

function devicesSection(): LandingSection
{
    return LandingSection::whereHas('page', fn ($q) => $q->where('slug', 'pricing'))->where('section_key', 'devices')->firstOrFail();
}

it('puts TenaFi’s device photos in the /pricing slots, keeping an admin’s own upload', function () {
    $section = devicesSection();
    // An old bundled drawing, and a picture an admin uploaded.
    LandingMedia::updateOrCreate(['section_id' => $section->id, 'media_key' => 'device_0_image'], ['original_path' => '/images/devices/tenafi-lite.svg', 'mime_type' => 'image/svg+xml', 'file_size' => 0]);
    LandingMedia::updateOrCreate(['section_id' => $section->id, 'media_key' => 'device_1_gallery_1'], ['original_path' => '/storage/landing/our-restaurant.jpg', 'mime_type' => 'image/jpeg', 'file_size' => 1]);
    LandingPage::clearCache();

    (require database_path('migrations/2026_11_19_000004_pricing_device_photos.php'))->up();

    $media = collect(LandingPage::publicSections('pricing'))->firstWhere('section_key', 'devices')['media'];
    expect($media['device_0_image'])->toBe('/images/devices/photos/tenafi-lite.webp')
        ->and($media['device_2_gallery_1'])->toBe('/images/devices/photos/outdoor-wedding.webp')
        ->and($media['install_4_image'])->toBe('/images/devices/photos/install-ceiling-mounting.webp')
        ->and($media['device_1_gallery_1'])->toBe('/storage/landing/our-restaurant.jpg')
        ->and($media)->not->toHaveKey('install_1_image');

    foreach (['tenafi-lite', 'pro-restaurant', 'install-connect'] as $file) {
        expect(file_exists(public_path("images/devices/photos/{$file}.webp")))->toBeTrue();
    }
});
