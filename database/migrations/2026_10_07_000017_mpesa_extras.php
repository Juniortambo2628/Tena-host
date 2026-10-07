<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Guests pay for extras (late checkout, cleaning, welcome packs) by M-Pesa
 * on TenaFi's paybill. TenaFi keeps its fee and settles the rest with the
 * host; settled_at records when that happened.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // not_required (free), unpaid (pay at the property), pending, paid, failed
            $table->string('payment_status', 16)->default('not_required');
            $table->string('payer_phone', 20)->nullable();
            $table->string('mpesa_checkout_request_id')->nullable()->index();
            $table->string('mpesa_receipt')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->decimal('platform_fee', 10, 2)->default(0);
            $table->timestamp('settled_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['mpesa_checkout_request_id']);
            $table->dropColumn(['payment_status', 'payer_phone', 'mpesa_checkout_request_id', 'mpesa_receipt', 'paid_at', 'platform_fee', 'settled_at']);
        });
    }
};
