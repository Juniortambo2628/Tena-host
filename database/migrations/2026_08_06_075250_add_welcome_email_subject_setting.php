<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->updateOrInsert(
            ['key' => 'welcome_email_subject'],
            ['value' => 'Welcome to TENA', 'group' => 'email_templates', 'type' => 'string', 'created_at' => now(), 'updated_at' => now()]
        );
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'welcome_email_subject')->delete();
    }
};
