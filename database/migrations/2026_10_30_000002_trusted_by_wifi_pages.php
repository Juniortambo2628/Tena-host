<?php

use App\Models\LandingPage;
use App\Models\LandingSection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * "Trusted by" now shows each client's WiFi page in a phone frame. Every
 * existing partner row gets the two new fields (kind, type_area) without
 * touching anything an admin wrote, and the section moves directly below
 * the hero. The rest of the homepage keeps the order set in the admin.
 */
return new class extends Migration
{
    /** Kinds for the five partners we launched with; anyone else is left for the admin to set. */
    private const KNOWN = [
        'Halo Studios' => ['business', ''],
        'Zero Sips' => ['business', ''],
        'Georgio Lani Pharmacy' => ['business', ''],
        'Stay Awhile Rentals' => ['hosts', "Serviced apartments \u{00B7} Nairobi"],
        'Luxury Hideaway' => ['hosts', ''],
    ];

    public function up(): void
    {
        $home = LandingPage::where('slug', 'home')->first();
        $partners = $home ? LandingSection::where('page_id', $home->id)->where('section_key', 'partners')->first() : null;
        if (! $partners) {
            return;
        }

        $content = $partners->contents()->pluck('value', 'content_key');
        $now = now();
        $rows = [];
        foreach ($content as $key => $name) {
            if (! preg_match('/^partners\.(\d+)\.name$/', $key, $m)) {
                continue;
            }
            [$kind, $typeArea] = self::KNOWN[trim((string) $name)] ?? ['', ''];
            foreach (['kind' => $kind, 'type_area' => $typeArea] as $field => $value) {
                if (! $content->has("partners.{$m[1]}.{$field}")) {
                    $rows[] = ['section_id' => $partners->id, 'content_key' => "partners.{$m[1]}.{$field}", 'value' => $value, 'type' => 'text', 'created_at' => $now, 'updated_at' => $now];
                }
            }
        }
        if ($rows) {
            DB::table('landing_content')->insert($rows);
        }

        // Directly below the hero; everything else keeps its relative order.
        $sections = LandingSection::where('page_id', $home->id)->orderBy('sort_order')->orderBy('id')->get()
            ->reject(fn (LandingSection $s) => $s->is($partners))->values();
        $hero = $sections->search(fn (LandingSection $s) => $s->section_key === 'hero');
        $sections->splice($hero === false ? 0 : $hero + 1, 0, [$partners]);
        foreach ($sections as $i => $section) {
            if ($section->sort_order !== $i + 1) {
                $section->update(['sort_order' => $i + 1]);
            }
        }

        LandingPage::clearCache();
    }

    public function down(): void
    {
        //
    }
};
