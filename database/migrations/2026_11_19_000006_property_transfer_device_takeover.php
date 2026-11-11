<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Property transfers, step 4 (docs/plans/property-transfer.md): a property
 * whose TenaFi device is still being paid off monthly can move if the new
 * owner takes over the remaining payments. What was owed when it was offered
 * is kept, so the hand-over can check nothing was added since.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_transfers', function (Blueprint $table) {
            $table->boolean('device_takeover')->default(false);
            $table->unsignedTinyInteger('device_count')->default(0);
            $table->unsignedInteger('device_balance_kes')->default(0);
            $table->unsignedInteger('device_monthly_kes')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('property_transfers', function (Blueprint $table) {
            $table->dropColumn(['device_takeover', 'device_count', 'device_balance_kes', 'device_monthly_kes']);
        });
    }
};
