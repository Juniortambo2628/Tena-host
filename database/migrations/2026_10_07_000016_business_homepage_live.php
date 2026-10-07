<?php

use App\Services\Cms\PageBlueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * The business customer homepage has shipped (/places/{id}): drop its badge.
 */
return new class extends Migration
{
    public function up(): void
    {
        PageBlueprint::setFeatureStatus('business_homepage', 'live');
    }

    public function down(): void
    {
        PageBlueprint::setFeatureStatus('business_homepage', 'coming_soon');
    }
};
