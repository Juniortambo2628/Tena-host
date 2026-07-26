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
        // Map the ambiguous 'user' role to 'guest' before narrowing the enum.
        DB::table('users')->where('role', 'user')->update(['role' => 'guest']);

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'staff', 'host', 'guest'])->default('guest')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Map new roles back to 'user' before reverting the enum.
        DB::table('users')->whereIn('role', ['guest', 'staff'])->update(['role' => 'user']);

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'user', 'host'])->default('user')->change();
        });
    }
};
