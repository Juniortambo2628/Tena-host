<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * How a property's thank-you and review requests reach guests: WhatsApp (as
 * before), a branded email, or both (email when the guest gave one, else
 * WhatsApp). Existing properties keep WhatsApp.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->string('review_request_channel', 10)->default('whatsapp')->after('review_message');
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn('review_request_channel');
        });
    }
};
