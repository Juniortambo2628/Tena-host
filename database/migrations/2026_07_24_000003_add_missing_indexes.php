<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index('guest_id');
            $table->index('property_id');
            $table->index('amenity_id');
            $table->index('status');
        });

        Schema::table('mpesa_transactions', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('Status');
        });

        Schema::table('registrations', function (Blueprint $table) {
            $table->index('status');
            $table->index('email');
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->index('key');
            $table->index('group');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['guest_id']);
            $table->dropIndex(['property_id']);
            $table->dropIndex(['amenity_id']);
            $table->dropIndex(['status']);
        });

        Schema::table('mpesa_transactions', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['Status']);
        });

        Schema::table('registrations', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['email']);
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->dropIndex(['key']);
            $table->dropIndex(['group']);
        });
    }
};
