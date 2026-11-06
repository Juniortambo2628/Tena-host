<?php

use App\Models\LandingPage;
use App\Services\Cms\V2Pages;
use Illuminate\Database\Migrations\Migration;

/**
 * /v2/hosts, /v2/business and /v2/events as their own CMS pages (Admin ->
 * Public Pages -> "Hosts (v2 design)", ...), copied from the live pages so
 * they start with the live words and pictures. Shown only behind the
 * "V2 public pages" switch, like /v2.
 */
return new class extends Migration
{
    public function up(): void
    {
        V2Pages::createAudiences();
    }

    public function down(): void
    {
        $slugs = array_map(fn ($audience) => V2Pages::audienceSlug($audience), array_keys(V2Pages::AUDIENCES));
        LandingPage::whereIn('slug', $slugs)->get()->each(function (LandingPage $page) {
            $page->sections()->each(fn ($section) => $section->delete());
            $page->delete();
        });
        LandingPage::clearCache();
    }
};
