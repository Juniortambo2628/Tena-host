<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Property transfers, step 2 (docs/plans/property-transfer.md): guests of a
 * transferred property are told who runs it now, and the new owner can't send
 * them campaigns, birthday treats or review requests for 14 days, so they have
 * time to opt out (Data Protection Act, 2019: a change of data controller).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->timestamp('campaign_hold_until')->nullable()->index();
        });

        Schema::table('property_transfers', function (Blueprint $table) {
            $table->string('notice_status', 20)->nullable(); // pending, sent, none
            $table->unsignedBigInteger('notice_cursor')->default(0); // last guest id notified, to resume in batches
            $table->unsignedInteger('notice_total')->default(0);
            $table->unsignedInteger('notice_sent')->default(0);
            $table->timestamp('hold_until')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('property_transfers', function (Blueprint $table) {
            $table->dropColumn(['notice_status', 'notice_cursor', 'notice_total', 'notice_sent', 'hold_until']);
        });
        Schema::table('guests', function (Blueprint $table) {
            $table->dropColumn('campaign_hold_until');
        });
    }
};
