<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Each M-Pesa STK prompt gets its own random token, sent to Safaricom in the
 * CallBackURL (/api/mpesa/callback/{token}). A callback is applied only when
 * its token matches the stored one, so knowing a CheckoutRequestID is no
 * longer enough to mark a payment as paid.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mpesa_transactions', function (Blueprint $table) {
            $table->string('callback_token', 64)->nullable()->unique();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('mpesa_callback_token', 64)->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('mpesa_transactions', function (Blueprint $table) {
            $table->dropUnique(['callback_token']);
            $table->dropColumn('callback_token');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['mpesa_callback_token']);
            $table->dropColumn('mpesa_callback_token');
        });
    }
};
