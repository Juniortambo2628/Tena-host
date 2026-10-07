<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WiFi login captures guests by WhatsApp number with an optional email, so
 * email becomes nullable. Consent is recorded per guest (Kenya Data
 * Protection Act, 2019): the wording shown, when, and whether they also
 * opted in to marketing. The device MAC lets a returning guest reconnect
 * with one tap.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->boolean('marketing_opt_in')->default(false)->after('source');
            $table->text('consent_text')->nullable()->after('marketing_opt_in');
            $table->timestamp('consented_at')->nullable()->after('consent_text');
            $table->string('device_mac', 17)->nullable()->after('consented_at');
            $table->index(['property_id', 'phone']);
            $table->index(['property_id', 'device_mac']);
        });
    }

    public function down(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->dropIndex(['property_id', 'phone']);
            $table->dropIndex(['property_id', 'device_mac']);
            $table->dropColumn(['marketing_opt_in', 'consent_text', 'consented_at', 'device_mac']);
        });
    }
};
