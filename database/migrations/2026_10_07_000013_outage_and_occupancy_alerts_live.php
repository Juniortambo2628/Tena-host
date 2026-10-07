<?php

use App\Services\Cms\PageBlueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Outage and occupancy alerts have shipped (alerts:check): drop their
 * "Coming soon" badges.
 */
return new class extends Migration
{
    public function up(): void
    {
        PageBlueprint::setFeatureStatus('outage_alerts', 'live');
        PageBlueprint::setFeatureStatus('occupancy_alerts', 'live');
    }

    public function down(): void
    {
        PageBlueprint::setFeatureStatus('outage_alerts', 'coming_soon');
        PageBlueprint::setFeatureStatus('occupancy_alerts', 'coming_soon');
    }
};
