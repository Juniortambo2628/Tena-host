<?php

use App\Models\LandingSection;
use App\Services\Cms\PageBlueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Every public page now shares the same header links; each audience page
 * appears as a megamenu built from its own sections' "menu_label" entries
 * (LandingPage::navMenus). The per-page "nav" sections are retired, and an
 * additive blueprint sync adds the menu labels without touching CMS edits.
 */
return new class extends Migration
{
    public function up(): void
    {
        LandingSection::where('section_key', 'nav')->get()->each->delete();

        PageBlueprint::sync();
    }

    public function down(): void
    {
        // Content-only; the nav sections are not restored.
    }
};
