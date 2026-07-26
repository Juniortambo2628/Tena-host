<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('analytics', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('metric_value')->index();
            $table->foreignId('property_id')->nullable()->after('user_id')->index();
            $table->foreignId('campaign_id')->nullable()->after('property_id')->index();

            $table->index(['metric_name', 'date_recorded']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('analytics', function (Blueprint $table) {
            $table->dropColumn(['user_id', 'property_id', 'campaign_id']);
            $table->dropIndex(['metric_name', 'date_recorded']);
        });
    }
};
