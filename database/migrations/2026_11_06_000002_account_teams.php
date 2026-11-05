<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Teams: a person the account owner invited (their own login) points at
 * the owner. team_role is "manager" (the dashboard, for their sites) or
 * "staff" (the staff dashboard); which sites is the existing staff_property
 * table. Removing the owner removes their team.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('team_owner_id')->nullable()->after('account_profile')->constrained('users')->cascadeOnDelete();
            $table->string('team_role', 16)->nullable()->after('team_owner_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('team_owner_id');
            $table->dropColumn('team_role');
        });
    }
};
