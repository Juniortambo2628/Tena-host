<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hosts pay per unit on Basic, Starter or Growth (config/billing.php). The
 * user keeps their current selection; each payment records the quote it
 * paid for, so the subscription can be extended by the right number of
 * months when M-Pesa confirms.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('billing_plan', 16)->nullable();
            $table->unsignedInteger('billing_units')->default(1);
            $table->unsignedInteger('billing_extra_devices')->default(0);
            $table->string('billing_cycle', 16)->default('monthly');
        });

        Schema::table('mpesa_transactions', function (Blueprint $table) {
            $table->json('meta')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['billing_plan', 'billing_units', 'billing_extra_devices', 'billing_cycle']);
        });

        Schema::table('mpesa_transactions', function (Blueprint $table) {
            $table->dropColumn('meta');
        });
    }
};
