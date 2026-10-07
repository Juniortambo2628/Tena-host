<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Tena is now TenaFi: move the old default name and logo over. Custom
 * values an admin typed in are left alone.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->where('key', 'site_name')->whereIn('value', ['Tena', 'TENA', 'Tena Host', 'Tena Platform'])->update(['value' => 'TenaFi']);
        DB::table('settings')->where('key', 'logo_url')->where('value', 'like', '%Tena-logo-square.jpg')->update(['value' => '/brand/tenafi-logo.svg']);

        // Email copy still on its seeded default (edited copy is left alone).
        foreach ([
            'welcome_email_heading' => ['Welcome to Tena Host!', 'Welcome to TenaFi!'],
            'welcome_email_body' => ['Thank you for joining Tena Host. We are excited to have you on board!', 'Thank you for joining TenaFi. We are excited to have you on board!'],
            'waitlist_confirmation_subject' => ["You're on the Tena waitlist!", "You're on the TenaFi waitlist!"],
            'waitlist_welcome_subject' => ['Welcome to the Tena Family!', 'Welcome to the TenaFi family!'],
            'waitlist_welcome_heading' => ['Welcome to the Tena Family!', 'Welcome to the TenaFi family!'],
        ] as $key => [$old, $new]) {
            DB::table('settings')->where('key', $key)->where('value', $old)->update(['value' => $new]);
        }

        Cache::forget('app_settings');
    }

    public function down(): void
    {
        //
    }
};
