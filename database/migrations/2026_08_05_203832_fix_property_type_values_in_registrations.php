<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('registrations')->where('property_type', 'vacation_rental')->update(['property_type' => 'Entire Place']);
        DB::table('registrations')->where('property_type', 'hotel')->update(['property_type' => 'Hotel / Boutique']);
        DB::table('registrations')->where('property_type', 'b&b')->update(['property_type' => 'Hotel / Boutique']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('registrations')->where('property_type', 'Entire Place')->update(['property_type' => 'vacation_rental']);
        DB::table('registrations')->where('property_type', 'Hotel / Boutique')->update(['property_type' => 'hotel']);
    }
};
