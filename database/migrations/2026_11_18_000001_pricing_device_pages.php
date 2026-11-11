<?php

use App\Models\LandingPage;
use App\Services\Cms\PageBlueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Each TenaFi device gets its own page (/pricing/lite, /pricing/pro,
 * /pricing/outdoor): a tagline, specifications, highlights and what's
 * included, added to the /pricing devices section from the blueprint. Only
 * missing fields are added; words an admin has edited stay as they are.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! LandingPage::where('slug', 'pricing')->exists()) {
            return;
        }

        PageBlueprint::addSections('pricing', ['devices' => 'pay_options']);
        LandingPage::clearCache();
    }

    public function down(): void
    {
        //
    }
};
