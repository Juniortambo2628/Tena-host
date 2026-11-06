<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The WiFi log (Admin -> WiFi Portal -> Logs): connection tests, guest
 * sign-ins authorized or refused by the controller, controller login
 * failures and access points going up or down. Kept for 30 days.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wifi_events', function (Blueprint $table) {
            $table->id();
            $table->string('level', 10)->default('info'); // info, warning, error
            $table->string('type', 40);
            $table->string('message', 500);
            $table->json('context')->nullable();
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['type', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wifi_events');
    }
};
