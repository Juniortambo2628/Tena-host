<?php

use App\Models\LandingMedia;
use App\Models\LandingPage;
use App\Models\LandingSection;
use Illuminate\Database\Migrations\Migration;

/**
 * TenaFi's own device photos (generated from docs/plans/device-images.md) in
 * the /pricing devices section: each device's main picture, three pictures on
 * its own page, and the "How it's installed" steps. Replaces the bundled
 * drawings; a picture an admin uploaded (/storage/...) is left alone.
 */
return new class extends Migration
{
    private const DIR = '/images/devices/photos/';

    private const PHOTOS = [
        'device_0_image' => 'tenafi-lite.webp',
        'device_1_image' => 'tenafi-pro.webp',
        'device_2_image' => 'tenafi-outdoor.webp',
        'device_0_gallery_1' => 'lite-apartment.webp',
        'device_0_gallery_2' => 'lite-salon.webp',
        'device_0_gallery_3' => 'lite-closeup.webp',
        'device_1_gallery_1' => 'pro-restaurant.webp',
        'device_1_gallery_2' => 'pro-hotel.webp',
        'device_1_gallery_3' => 'pro-lounge.webp',
        'device_2_gallery_1' => 'outdoor-wedding.webp',
        'device_2_gallery_2' => 'outdoor-terrace.webp',
        'device_2_gallery_3' => 'outdoor-closeup.webp',
        'install_0_image' => 'install-connect.webp',
        'install_2_image' => 'install-wall-mounting.webp',
        'install_3_image' => 'install-ceiling.webp',
        'install_4_image' => 'install-ceiling-mounting.webp',
    ];

    public function up(): void
    {
        $section = LandingSection::whereHas('page', fn ($q) => $q->where('slug', 'pricing'))->where('section_key', 'devices')->first();
        if (! $section) {
            return;
        }

        foreach (self::PHOTOS as $key => $photo) {
            $current = LandingMedia::where('section_id', $section->id)->where('media_key', $key)->first();
            if ($current && str_starts_with((string) $current->original_path, '/storage/')) {
                continue; // the admin's own upload
            }
            LandingMedia::updateOrCreate(
                ['section_id' => $section->id, 'media_key' => $key],
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
