<?php

use App\Models\LandingPage;
use App\Services\Cms\PageBlueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * The /pricing page (TenaFi Lite, Pro and Outdoor, and the two ways to pay),
 * from the blueprint. It stays off until Admin -> Settings "Pricing page" or
 * `php artisan tenafi:pricing-devices` switches it on; admins can preview it.
 */
return new class extends Migration
{
    public function up(): void
    {
        LandingPage::firstOrCreate(['slug' => 'pricing'], [
            'name' => 'Devices & pricing',
            'is_routable' => true,
            'is_active' => true,
            'sort_order' => (int) LandingPage::max('sort_order') + 1,
        ]);

        PageBlueprint::addSections('pricing', array_fill_keys(array_keys(PageBlueprint::definitions()['pricing']), ''));

        LandingPage::clearCache();
    }

    public function down(): void
    {
        //
    }
};
