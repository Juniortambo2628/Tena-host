<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Account foundations for businesses and events (users.account_type also
 * takes "event" now; it is already a string column):
 * - users.account_profile: an event account's kind (venue, organiser or both)
 * - properties.region: groups a business's sites ("Nairobi branches")
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('account_profile', 16)->nullable()->after('account_type');
        });

        Schema::table('properties', function (Blueprint $table) {
            $table->string('region', 80)->nullable()->after('address');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('account_profile');
        });

        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn('region');
        });
    }
};
