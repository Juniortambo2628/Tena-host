<?php

use App\Models\LandingPage;
use App\Services\Cms\PageBlueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * The three-audience homepage: adds the sections the live homepage doesn't
 * have yet (and the Site-wide announcement bar), all hidden, so nothing
 * changes until they're switched on. `php artisan tenafi:homepage-copy`
 * shows the copy changes, backs up and then applies them and switches the
 * new sections on. Existing sections are never touched here.
 */
return new class extends Migration
{
    public function up(): void
    {
        PageBlueprint::addSections('home', [
            'features__plays' => 'how_it_works',
            'detailed_features__home' => 'how_it_works',
            'comparison_table' => 'cta_banner__founding',
            'credibility' => 'cta_banner__founding',
            'faq' => '',
        ], active: false);

        PageBlueprint::addSections(LandingPage::SITE, ['announcement' => 'footer'], active: false);
    }

    public function down(): void
    {
        //
    }
};
