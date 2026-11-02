<?php

use App\Models\LandingPage;
use App\Services\Cms\PageBlueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * The homepage's new sections on /hosts and /business: live demo, "What's it
 * worth" calculator, the free WiFi vs TenaFi table, and (on /business) the
 * dark proof section. Added hidden, so both pages look the same until
 * `php artisan tenafi:audience-pages` switches them on with the rest of the
 * new look.
 */
return new class extends Migration
{
    public function up(): void
    {
        PageBlueprint::addSections('hosts', [
            'demo' => 'stats__commission',
            'worth_calculator' => 'comparison__party',
            'comparison_table' => 'detailed_features',
        ], active: false);

        PageBlueprint::addSections('business', [
            'demo' => 'comparison__qr',
            'comparison_table' => 'features__industries',
            'credibility' => 'journey',
            'worth_calculator' => 'credibility',
        ], active: false);

        LandingPage::clearCache();
    }

    public function down(): void
    {
        //
    }
};
