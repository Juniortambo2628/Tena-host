<?php

use App\Services\Cms\PageBlueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Guest extras can be paid by M-Pesa (TenaFi's paybill): drop the badge.
 */
return new class extends Migration
{
    public function up(): void
    {
        PageBlueprint::setFeatureStatus('mpesa_extras', 'live');
    }

    public function down(): void
    {
        PageBlueprint::setFeatureStatus('mpesa_extras', 'coming_soon');
    }
};
