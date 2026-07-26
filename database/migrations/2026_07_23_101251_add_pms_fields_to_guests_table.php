<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->string('external_id')->nullable()->after('source')->index();
            $table->date('check_in')->nullable()->after('external_id');
            $table->date('check_out')->nullable()->after('check_in');
        });
    }

    public function down(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->dropColumn(['external_id', 'check_in', 'check_out']);
        });
    }
};
