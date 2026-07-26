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
        Schema::table('campaigns', function (Blueprint $table) {
            $table->foreignId('audience_property_id')->nullable()->after('property_id')->constrained('properties')->nullOnDelete();
            $table->date('audience_from')->nullable()->after('target_audience');
            $table->date('audience_to')->nullable()->after('audience_from');
            $table->string('schedule_trigger')->nullable()->after('trigger_delay');
            $table->timestamp('scheduled_at')->nullable()->after('schedule_trigger');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropForeign(['audience_property_id']);
            $table->dropColumn(['audience_property_id', 'audience_from', 'audience_to', 'schedule_trigger', 'scheduled_at']);
        });
    }
};
