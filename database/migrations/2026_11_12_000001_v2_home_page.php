<?php

use App\Models\LandingPage;
use App\Services\Cms\V2Pages;
use Illuminate\Database\Migrations\Migration;

/**
 * The /v2 homepage as its own CMS page (Admin -> Public Pages -> "Home (v2
 * design)"), copied from the live homepage's sections so it starts with the
 * live words and pictures. Shown only behind the "V2 public pages" switch.
 */
return new class extends Migration
{
    public function up(): void
    {
        V2Pages::createHome();
    }

    public function down(): void
    {
        LandingPage::where('slug', V2Pages::HOME)->get()->each(function (LandingPage $page) {
            $page->sections()->each(fn ($section) => $section->delete());
            $page->delete();
        });
        LandingPage::clearCache();
    }
};
