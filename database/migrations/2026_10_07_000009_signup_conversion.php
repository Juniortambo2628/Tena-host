<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sign-ups become accounts. Many sign up with only a WhatsApp number, so
 * a user may have no email and sign in with their phone; the sign-up keeps
 * a link to the account it created.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email', 100)->nullable()->change();
            $table->index('phone_number');
        });

        Schema::table('registrations', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['phone_number']);
        });
    }
};
