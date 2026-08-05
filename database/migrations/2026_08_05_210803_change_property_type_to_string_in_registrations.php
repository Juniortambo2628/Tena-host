<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `registrations` MODIFY COLUMN `property_type` VARCHAR(50) NOT NULL DEFAULT 'other'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `registrations` MODIFY COLUMN `property_type` ENUM('vacation_rental','hotel','b&b','other') NOT NULL DEFAULT 'other'");
    }
};
