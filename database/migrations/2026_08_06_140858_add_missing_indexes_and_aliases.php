<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->index('user_id');
        });

        Schema::table('access_points', function (Blueprint $table) {
            $table->index('property_id');
        });

        Schema::table('guests', function (Blueprint $table) {
            $table->index('property_id');
        });

        Schema::table('amenities', function (Blueprint $table) {
            $table->index('property_id');
        });

        Schema::table('campaigns', function (Blueprint $table) {
            $table->index('property_id');
            $table->index('audience_property_id');
        });

        Schema::table('marketing_events', function (Blueprint $table) {
            $table->index('guest_id');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::table('subscription_items', function (Blueprint $table) {
            $table->foreign('subscription_id')->references('id')->on('subscriptions')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
        });

        Schema::table('access_points', function (Blueprint $table) {
            $table->dropIndex(['property_id']);
        });

        Schema::table('guests', function (Blueprint $table) {
            $table->dropIndex(['property_id']);
        });

        Schema::table('amenities', function (Blueprint $table) {
            $table->dropIndex(['property_id']);
        });

        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropIndex(['property_id']);
            $table->dropIndex(['audience_property_id']);
        });

        Schema::table('marketing_events', function (Blueprint $table) {
            $table->dropIndex(['guest_id']);
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('subscription_items', function (Blueprint $table) {
            $table->dropForeign(['subscription_id']);
        });
    }
};
