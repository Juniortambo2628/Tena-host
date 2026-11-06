<?php

use App\Models\LandingContent;
use App\Models\LandingPage;
use App\Models\Setting;
use App\Services\Cms\GarbledText;
use App\Services\Cms\PageBlueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;

/**
 * Text saved by the old cPanel server (up to late October 2026) lost its
 * special characters: each byte became "?" ("It isn???t just OTA fees").
 * The v2 pages copied some of it. Restore each text from the page blueprint
 * when it is still the blueprint's wording, and mend the rest from context.
 * Only the characters change; nothing else in the text is touched.
 */
return new class extends Migration
{
    public function up(): void
    {
        $definitions = PageBlueprint::definitions();

        LandingContent::query()->where('value', 'like', '%??%')->with('section.page')->each(function (LandingContent $content) use ($definitions) {
            if (! GarbledText::isGarbled($content->value)) {
                return;
            }
            // A v2 page's text came from its live page: v2 -> home, v2-hosts -> hosts.
            $slug = (string) $content->section?->page?->slug;
            $slug = $slug === 'v2' ? 'home' : (str_starts_with($slug, 'v2-') ? substr($slug, 3) : $slug);
            $definition = $definitions[$slug][$content->section?->section_key]['content'] ?? [];
            $blueprint = PageBlueprint::flatten($definition)[$content->content_key] ?? null;

            $repaired = GarbledText::fromBlueprint($content->value, $blueprint) ?? GarbledText::mend($content->value);
            if ($repaired !== $content->value) {
                $content->update(['value' => $repaired]);
            }
        });

        // The old waitlist emails' wording, in settings. Never anything secret-looking.
        Setting::query()->where('value', 'like', '%??%')->where('key', 'not like', '%key%')
            ->where('key', 'not like', '%secret%')->where('key', 'not like', '%token%')->where('key', 'not like', '%password%')
            ->each(function (Setting $setting) {
                if (GarbledText::isGarbled($setting->value)) {
                    $setting->update(['value' => GarbledText::mend($setting->value)]);
                }
            });

        LandingPage::clearCache();
        Cache::forget('app_settings');
    }

    public function down(): void
    {
        // Nothing to undo: the old text was damaged.
    }
};
