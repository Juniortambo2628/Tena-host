<?php

use App\Models\LandingPage;
use App\Models\LandingSection;
use App\Services\Cms\PageBlueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * The /events page (events, weddings and venues), created hidden: /events
 * stays a 404 until `php artisan tenafi:homepage-copy` switches it on with
 * the new homepage, whose Events links point here. Also adds the WhatsApp
 * button's events fields, empty, so nothing changes until then.
 */
return new class extends Migration
{
    public function up(): void
    {
        LandingPage::firstOrCreate(['slug' => 'events'], [
            'name' => 'Events, weddings & venues',
            'is_routable' => true,
            'is_active' => false,
            'sort_order' => (int) LandingPage::max('sort_order') + 1,
        ]);

        PageBlueprint::addSections('events', array_fill_keys(array_keys(PageBlueprint::definitions()['events']), ''));

        $button = LandingSection::where('section_key', 'whatsapp_button')
            ->whereHas('page', fn ($q) => $q->where('slug', LandingPage::SITE))
            ->first();
        foreach (['message_events', 'menu_events'] as $key) {
            $button?->contents()->firstOrCreate(['content_key' => $key], ['value' => '', 'type' => 'text']);
        }

        LandingPage::clearCache();
    }

    public function down(): void
    {
        //
    }
};
