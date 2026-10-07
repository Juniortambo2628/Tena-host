<?php

use App\Services\Cms\PageBlueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * The monthly report has shipped (reports:monthly): drop its badge.
 */
return new class extends Migration
{
    public function up(): void
    {
        PageBlueprint::setFeatureStatus('monthly_report', 'live');
    }

    public function down(): void
    {
        PageBlueprint::setFeatureStatus('monthly_report', 'coming_soon');
    }
};
