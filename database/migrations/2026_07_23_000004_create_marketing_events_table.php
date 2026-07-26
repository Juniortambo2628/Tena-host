<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guest_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('event_type', ['sent', 'opened', 'clicked', 'bounced', 'unsubscribed']);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['campaign_id', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_events');
    }
};
