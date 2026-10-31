<?php

use App\Models\LandingPage;
use App\Models\LandingSection;
use App\Services\Cms\PageBlueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * "What's it worth" calculator on the homepage (added hidden) and the
 * WhatsApp button's optional menu fields (added empty, so the button keeps
 * working as before until `tenafi:homepage-copy` or an admin fills them).
 */
return new class extends Migration
{
    public function up(): void
    {
        PageBlueprint::addSections('home', ['worth_calculator' => 'credibility'], active: false);

        $button = LandingSection::where('section_key', 'whatsapp_button')
            ->whereHas('page', fn ($q) => $q->where('slug', LandingPage::SITE))
            ->first();
        foreach (['menu_greeting', 'menu_home', 'menu_hosts', 'menu_business'] as $key) {
            $button?->contents()->firstOrCreate(['content_key' => $key], ['value' => '', 'type' => 'text']);
        }

        LandingPage::clearCache();
    }

    public function down(): void
    {
        //
    }
};
