<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Glen's sign-up spec (SIGNUP-FIELDS.md): the WhatsApp number is the
 * required contact and email is optional, so sign-ups are keyed on phone.
 * New sign-ups alert glen@tena.host unless recipients are already set.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->string('email', 100)->nullable()->change();
            $table->index(['type', 'phone']);
        });

        if (! Setting::where('key', 'signup_alert_emails')->exists()) {
            Setting::setValue('signup_alert_emails', 'glen@tena.host');
        }
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropIndex(['type', 'phone']);
        });
    }
};
