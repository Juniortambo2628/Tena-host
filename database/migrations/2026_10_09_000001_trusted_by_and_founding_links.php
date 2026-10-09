<?php

use App\Models\LandingContent;
use App\Models\LandingPage;
use App\Models\LandingSection;
use App\Services\Cms\PageBlueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Homepage gets a "Trusted by" logo strip (partners section, logos uploaded
 * in Admin → Public Pages). The old site's partners placeholder, if any,
 * is replaced. The Founding 20 buttons now open the top of each audience
 * page instead of jumping to its sign-up form.
 */
return new class extends Migration
{
    public function up(): void
    {
        $home = LandingPage::where('slug', 'home')->first();

        if ($home) {
            LandingSection::where('page_id', $home->id)->where('section_key', 'partners')->get()->each->delete();
        }

        PageBlueprint::sync();

        if ($home) {
            // Put homepage sections in blueprint order (sync only orders new ones).
            $order = array_flip(array_keys(PageBlueprint::definitions()['home']));
            LandingSection::where('page_id', $home->id)->get()->each(function (LandingSection $section) use ($order) {
                if (isset($order[$section->section_key])) {
                    $section->update(['sort_order' => $order[$section->section_key] + 1]);
                }
            });
        }

        LandingContent::whereHas('section', fn ($q) => $q->where('section_key', 'cta_banner__founding'))
            ->whereIn('value', ['/hosts#join', '/business#signup'])
            ->get()
            ->each(fn (LandingContent $c) => $c->update(['value' => strtok($c->value, '#')]));

        LandingPage::clearCache();
    }

    public function down(): void
    {
        //
    }
};
