<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Staff WiFi (corporate sites): a site's separate staff network (its SSID)
 * gets a simple staff sign-in instead of the guest page, optionally limited
 * to a work email domain. Staff connections are kept apart from guests, so
 * staff never reach the customer list or campaigns.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->string('staff_ssid', 64)->nullable()->after('wifi_ssid');
            $table->string('staff_email_domain', 100)->nullable()->after('staff_ssid');
        });

        Schema::create('staff_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('identifier', 150);
            $table->string('device_mac', 17)->nullable();
            $table->unsignedInteger('visits')->default(1);
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
            $table->index(['property_id', 'device_mac']);
            $table->index(['property_id', 'last_seen_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_connections');
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn(['staff_ssid', 'staff_email_domain']);
        });
    }
};
