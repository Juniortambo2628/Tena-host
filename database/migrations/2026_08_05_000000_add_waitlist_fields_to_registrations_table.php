<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->string('units', 20)->nullable()->after('property_count');
            $table->string('primary_platform', 50)->nullable()->after('units');
            $table->string('biggest_challenge', 100)->nullable()->after('primary_platform');
            $table->boolean('agree_updates')->default(false)->after('biggest_challenge');
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropColumn(['units', 'primary_platform', 'biggest_challenge', 'agree_updates']);
        });
    }
};
