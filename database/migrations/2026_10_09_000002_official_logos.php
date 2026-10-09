<?php

use App\Models\LandingPage;
use App\Models\LandingSection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Official TenaFi logos ship as defaults in App\Support\Brand. Logo
 * settings still on an old built-in file are cleared so the defaults show;
 * uploaded logos are kept. The header logo now lives only in Settings →
 * Branding, so the duplicate CMS header slot goes.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->where('key', 'logo_url')
            ->where(fn ($q) => $q->where('value', 'like', '/brand/%')->orWhere('value', 'like', '%/legacy/assets/%'))
            ->delete();

        $header = LandingSection::where('section_key', 'header')->pluck('id');
        DB::table('landing_media')->whereIn('section_id', $header)->where('media_key', 'logo')->delete();

        DB::table('landing_media')->where('media_key', 'og_image')
            ->where('original_path', 'like', '/legacy/assets/Tena-logo-square%')
            ->update(['original_path' => '/brand/tenafi-logo.png', 'mime_type' => 'image/png']);

        Cache::forget('app_settings');
        LandingPage::clearCache();
    }

    public function down(): void {}
};
