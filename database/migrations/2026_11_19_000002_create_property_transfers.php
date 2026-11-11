<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Property transfers (docs/plans/property-transfer.md, step 1): an owner
 * hands one of their properties, with its WiFi page, devices, guest list and
 * history, to another TenaFi account. Only the owner can start one; the
 * recipient accepts or declines it, signed in with the invited email.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('to_user_id')->nullable()->constrained('users')->nullOnDelete(); // set on acceptance
            $table->string('to_email');
            $table->string('status', 20)->default('pending'); // pending, completed, declined, cancelled, expired
            $table->timestamp('expires_at');
            $table->timestamp('decided_at')->nullable(); // accepted, declined or cancelled
            $table->string('from_ip', 45)->nullable();
            $table->string('decided_ip', 45)->nullable();
            $table->json('moved')->nullable(); // counts of what moved, for the record
            $table->timestamps();
            $table->index(['to_email', 'status']);
            $table->index(['property_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_transfers');
    }
};
