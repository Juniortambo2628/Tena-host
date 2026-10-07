<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Campaigns can go out over WhatsApp as well as email and SMS. The type
 * becomes a plain string so adding a channel doesn't need an enum change.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->string('type', 16)->default('email')->change();
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->enum('type', ['email', 'sms'])->default('email')->change();
        });
    }
};
