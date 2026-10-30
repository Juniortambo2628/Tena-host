<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What kind of place a property is (apartment, café, salon, clinic...), from
 * resources/data/splash-templates.json. It picks the recommended splash
 * template. Nullable: existing properties keep working without one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->string('business_type', 32)->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn('business_type');
        });
    }
};
