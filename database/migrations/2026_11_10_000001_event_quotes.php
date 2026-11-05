<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pay per event: an organiser without a plan asks for a quote (expected
 * attendees and a note), TenaFi sets the price, and the organiser pays it by
 * M-Pesa or card. Paid (or plan-covered) events get the event WiFi page.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->unsignedInteger('expected_attendees')->nullable()->after('sponsor');
            // requested -> quoted -> paid
            $table->string('quote_status', 20)->nullable()->after('expected_attendees');
            $table->unsignedInteger('quote_amount')->nullable()->after('quote_status');
            $table->string('quote_note', 300)->nullable()->after('quote_amount');
            $table->string('request_note', 300)->nullable()->after('quote_note');
            $table->timestamp('quote_requested_at')->nullable()->after('request_note');
            $table->timestamp('quoted_at')->nullable()->after('quote_requested_at');
            $table->timestamp('paid_at')->nullable()->after('quoted_at');
            $table->index('quote_status');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex(['quote_status']);
            $table->dropColumn(['expected_attendees', 'quote_status', 'quote_amount', 'quote_note', 'request_note', 'quote_requested_at', 'quoted_at', 'paid_at']);
        });
    }
};
