<?php

use App\Models\LandingMedia;
use App\Models\LandingPage;
use App\Models\LandingSection;
use Illuminate\Database\Migrations\Migration;

/**
 * The v2 "Trusted by" now shows each client's logo beside their name
 * (partner_{i}_mark) and their WiFi page design in a phone
 * (partner_{i}_logo). The previous homepage's Trusted by slots still hold
 * the clients' logos (square or wide pictures), so copy each one to the v2
 * client with the same name. Slots that already have a logo are left alone.
 */
return new class extends Migration
{
    public function up(): void
    {
        $from = $this->partners('home');
        $to = $this->partners('v2');
        if (! $from || ! $to) {
            return;
        }

        $names = fn (LandingSection $section) => $section->contents()->where('content_key', 'like', 'partners.%.name')->get()
            ->mapWithKeys(fn ($c) => [mb_strtolower(trim(strip_tags($c->value))) => (int) explode('.', $c->content_key)[1]]);
        $fromIndex = $names($from);

        foreach ($names($to) as $name => $i) {
            $j = $fromIndex[$name] ?? null;
            $logo = $j === null ? null : LandingMedia::where('section_id', $from->id)->where('media_key', "partner_{$j}_logo")->first();
            // Only logos: a tall picture there is a WiFi page screenshot, not a logo.
            if (! $logo || ! $logo->width || ! $logo->height || $logo->height / $logo->width >= 1.3) {
                continue;
            }
            if (LandingMedia::where('section_id', $to->id)->where('media_key', "partner_{$i}_mark")->exists()) {
                continue;
            }
            LandingMedia::create(['section_id' => $to->id, 'media_key' => "partner_{$i}_mark"] + $logo->only([
                'original_path', 'optimized_path', 'thumbnail_path', 'mime_type', 'file_size', 'width', 'height',
            ]));
        }

        LandingPage::clearCache();
    }

    private function partners(string $slug): ?LandingSection
    {
        return LandingSection::whereHas('page', fn ($q) => $q->where('slug', $slug))->where('section_key', 'partners')->first();
    }

    public function down(): void
    {
        //
    }
};
