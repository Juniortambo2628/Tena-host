<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Guests can reply STOP to any campaign. opted_out_at keeps them out of
 * every campaign; message_id ties WhatsApp read receipts to a recipient.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->timestamp('opted_out_at')->nullable();
        });

        Schema::table('campaign_recipients', function (Blueprint $table) {
            $table->string('message_id')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('campaign_recipients', function (Blueprint $table) {
            $table->dropIndex(['message_id']);
            $table->dropColumn('message_id');
        });

        Schema::table('guests', function (Blueprint $table) {
            $table->dropColumn('opted_out_at');
        });
    }
};
