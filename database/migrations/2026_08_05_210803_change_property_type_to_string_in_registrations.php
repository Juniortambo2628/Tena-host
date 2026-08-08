<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE `registrations` MODIFY COLUMN `property_type` VARCHAR(50) NOT NULL DEFAULT 'other'");
        } elseif ($driver === 'sqlite') {
            Schema::table('registrations', function (Blueprint $table) {
                $table->string('property_type_new', 50)->default('other');
            });
            DB::table('registrations')->update(['property_type_new' => DB::raw('property_type')]);
            Schema::table('registrations', function (Blueprint $table) {
                $table->dropColumn('property_type');
            });
            Schema::table('registrations', function (Blueprint $table) {
                $table->renameColumn('property_type_new', 'property_type');
            });
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `registrations` MODIFY COLUMN `property_type` ENUM('vacation_rental','hotel','b&b','other') NOT NULL DEFAULT 'other'");
        }
    }
};
