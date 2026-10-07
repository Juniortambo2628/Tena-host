<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Campaigns run on their own (campaigns:run): scheduled broadcasts go out
 * at their send time, trigger campaigns reach each guest after their event.
 * One recipient row per guest per campaign stops repeat sends and carries
 * the short token used to count opens and clicks.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('sent_at')->nullable(); // broadcast finished
        });

        Schema::create('campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guest_id')->constrained()->cascadeOnDelete();
            $table->string('token', 10)->unique();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->timestamps();

            $table->unique(['campaign_id', 'guest_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_recipients');

        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['activated_at', 'sent_at']);
        });
    }
};
