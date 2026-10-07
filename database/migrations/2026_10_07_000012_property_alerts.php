<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remember when an alert went out, so each outage is reported once (plus
 * a recovery notice) and occupancy alerts go out at most once a day.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('access_points', function (Blueprint $table) {
            $table->timestamp('outage_alerted_at')->nullable();
        });

        Schema::table('properties', function (Blueprint $table) {
            $table->timestamp('occupancy_alerted_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('access_points', function (Blueprint $table) {
            $table->dropColumn('outage_alerted_at');
        });

        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn('occupancy_alerted_at');
        });
    }
};
