<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Business customer homepage: what a café or salon shows customers after
 * they connect (menu = active amenities, plus offers and events), and an
 * optional birthday treat sent on the customer's birthday.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->text('offers')->nullable();
            $table->text('events')->nullable();
            $table->text('birthday_offer')->nullable();
        });

        Schema::table('guests', function (Blueprint $table) {
            $table->string('birthday', 5)->nullable(); // MM-DD, no year
            $table->unsignedSmallInteger('birthday_sent_year')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn(['offers', 'events', 'birthday_offer']);
        });

        Schema::table('guests', function (Blueprint $table) {
            $table->dropColumn(['birthday', 'birthday_sent_year']);
        });
    }
};
