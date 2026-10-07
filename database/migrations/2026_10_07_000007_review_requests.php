<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Automatic thank-you + review request after a stay. The host sets their
 * Google review link per property; each guest is asked once, and a click on
 * the short link (/r/{token}, short enough for one SMS) is recorded.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->string('review_url', 500)->nullable();
            $table->boolean('review_requests_enabled')->default(false);
            $table->unsignedSmallInteger('review_request_delay_hours')->default(24);
            $table->text('review_message')->nullable();
        });

        Schema::table('guests', function (Blueprint $table) {
            $table->timestamp('review_requested_at')->nullable();
            $table->timestamp('review_clicked_at')->nullable();
            $table->string('review_token', 12)->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn(['review_url', 'review_requests_enabled', 'review_request_delay_hours', 'review_message']);
        });

        Schema::table('guests', function (Blueprint $table) {
            $table->dropUnique(['review_token']);
            $table->dropColumn(['review_requested_at', 'review_clicked_at', 'review_token']);
        });
    }
};
