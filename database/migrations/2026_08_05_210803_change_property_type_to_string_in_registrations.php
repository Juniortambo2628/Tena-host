<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `registrations` MODIFY COLUMN `property_type` VARCHAR(50) NOT NULL DEFAULT 'other'");
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `registrations` MODIFY COLUMN `property_type` ENUM('vacation_rental','hotel','b&b','other') NOT NULL DEFAULT 'other'");
        }
    }
};
