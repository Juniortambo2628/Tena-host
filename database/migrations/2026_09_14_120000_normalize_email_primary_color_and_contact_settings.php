<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')
            ->where('key', 'email_primary_color')
            ->whereIn('value', ['#1a1a2e', '#1A1A2E'])
            ->update(['value' => '#000000', 'updated_at' => now()]);

        $contactSettings = [
            ['key' => 'contact_enquiry_heading', 'value' => 'New contact enquiry', 'group' => 'email_templates', 'type' => 'string'],
            ['key' => 'contact_enquiry_body', 'value' => '', 'group' => 'email_templates', 'type' => 'string'],
        ];

        foreach ($contactSettings as $setting) {
            DB::table('settings')->updateOrInsert(
                ['key' => $setting['key']],
                array_merge($setting, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }

    public function down(): void
    {
        DB::table('settings')
            ->whereIn('key', ['contact_enquiry_heading', 'contact_enquiry_body'])
            ->delete();
    }
};
