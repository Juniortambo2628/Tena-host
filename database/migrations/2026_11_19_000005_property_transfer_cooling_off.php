<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Property transfers, step 3 (docs/plans/property-transfer.md): an accepted
 * transfer waits 48 hours before the hand-over ("accepted", completes_at),
 * so the old owner can still cancel; TenaFi admins can hold or cancel one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_transfers', function (Blueprint $table) {
            $table->timestamp('completes_at')->nullable(); // end of the cooling-off
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancelled_by', 20)->nullable(); // owner, link, admin, system
            $table->timestamp('held_at')->nullable(); // an admin paused the hand-over
            $table->foreignId('held_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('admin_note', 500)->nullable();
            $table->index(['status', 'completes_at']);
        });
    }

    public function down(): void
    {
        Schema::table('property_transfers', function (Blueprint $table) {
            $table->dropIndex(['status', 'completes_at']);
            $table->dropConstrainedForeignId('held_by');
            $table->dropColumn(['completes_at', 'completed_at', 'cancelled_at', 'cancelled_by', 'held_at', 'admin_note']);
        });
    }
};
