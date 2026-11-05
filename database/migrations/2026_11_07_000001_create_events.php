<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Events (Phase 3): a dated event at a venue (a property), with its own
 * optional WiFi page design, an extra question and a sponsor; and who came
 * (event_guest: one row per attendee, the days they connected, their
 * answer). The WiFi portal starts using these in the next step.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->date('starts_on');
            $table->date('ends_on');
            $table->json('branding_json')->nullable();
            $table->string('question', 150)->nullable();
            $table->json('sponsor')->nullable();
            $table->timestamps();
            $table->index(['property_id', 'starts_on', 'ends_on']);
        });

        Schema::create('event_guest', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guest_id')->constrained()->cascadeOnDelete();
            $table->json('days')->nullable();
            $table->string('answer', 255)->nullable();
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
            $table->unique(['event_id', 'guest_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_guest');
        Schema::dropIfExists('events');
    }
};
