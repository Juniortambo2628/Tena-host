<?php

use App\Models\LandingPage;
use App\Models\LandingSection;
use App\Models\Setting;
use App\Services\Cms\PageBlueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Floating WhatsApp button and the "X of 50 free trial slots taken"
 * counter. Adds their Site-wide sections, a "show_slots" switch on the
 * pricing and sign-up sections, and the total in Settings. Nothing an
 * admin wrote is changed.
 */
return new class extends Migration
{
    public function up(): void
    {
        PageBlueprint::addSections(LandingPage::SITE, ['whatsapp_button' => '', 'trial_slots' => '']);

        $sections = LandingSection::whereIn('section_key', ['pricing', 'signup'])
            ->whereHas('page', fn ($q) => $q->whereIn('slug', ['hosts', 'business']))
            ->get();
        foreach ($sections as $section) {
            $section->contents()->firstOrCreate(['content_key' => 'show_slots'], ['value' => '1', 'type' => 'text']);
        }

        if (! Setting::where('key', 'trial_slots_total')->exists()) {
            Setting::setValue('trial_slots_total', '50');
        }

        LandingPage::clearCache();
    }

    public function down(): void
    {
        //
    }
};
