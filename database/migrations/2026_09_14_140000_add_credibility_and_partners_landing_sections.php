<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $now = now();

            // 1. Insert the credibility section BEFORE how_it_works.
            //    Bump every section at that spot or later down by 1 first, so the
            //    slot at sort_order = 3 becomes free without touching hero (1) or
            //    features (2). We only shift sections that already exist; if the
            //    schema has drifted this stays idempotent.
            $anchor = DB::table('landing_sections')
                ->where('section_key', 'how_it_works')
                ->value('sort_order');

            $insertAt = $anchor !== null ? (int) $anchor : (int) (DB::table('landing_sections')->max('sort_order') ?? 0) + 1;

            if ($anchor !== null) {
                DB::table('landing_sections')
                    ->where('sort_order', '>=', $insertAt)
                    ->increment('sort_order');
            }

            $credibilityExists = DB::table('landing_sections')
                ->where('section_key', 'credibility')
                ->exists();

            if (! $credibilityExists) {
                $credibilityId = DB::table('landing_sections')->insertGetId([
                    'section_key' => 'credibility',
                    'title' => 'Built by Superhosts. Built for SuperHosts.',
                    'subtitle' => 'The experience behind Tena — Stay Awhile Rentals.',
                    'bg' => 'gray',
                    'sort_order' => $insertAt,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $contents = [
                    ['content_key' => 'badge', 'value' => 'The experience behind Tena — Stay Awhile Rentals'],
                    ['content_key' => 'title', 'value' => 'Built by Superhosts. Built for SuperHosts.'],
                    ['content_key' => 'subtitle', 'value' => 'Tena was born from Stay Awhile Rentals — a real short-term rental business. After years of hosting thousands of guests, we saw firsthand how difficult it can be for hosts to build direct relationships with guests beyond the booking platform. That experience became Tena.'],
                    ['content_key' => 'closing_line', 'value' => "We experienced the problem ourselves. Now we're building the solution for Superhosts across Africa."],
                    ['content_key' => 'tagline', 'value' => 'Own the Guest. Build the Relationship.'],
                ];

                $stats = [
                    ['value' => '16×', 'label' => 'Superhost'],
                    ['value' => '1,400+', 'label' => 'Reservations'],
                    ['value' => '5,000+', 'label' => 'Guest Nights'],
                    ['value' => '750+', 'label' => 'Guest Reviews'],
                    ['value' => '4.9/5', 'label' => 'Guest Rating'],
                ];
                foreach ($stats as $i => $stat) {
                    $contents[] = ['content_key' => "stats.{$i}.value", 'value' => $stat['value']];
                    $contents[] = ['content_key' => "stats.{$i}.label", 'value' => $stat['label']];
                }

                $contentRows = array_map(fn ($c) => [
                    'section_id' => $credibilityId,
                    'content_key' => $c['content_key'],
                    'value' => $c['value'],
                    'type' => 'text',
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $contents);
                DB::table('landing_content')->insert($contentRows);

                $mediaRows = [
                    [
                        'section_id' => $credibilityId,
                        'media_key' => 'main_image',
                        'original_path' => '/legacy/assets/Tena-Landing/Tena-Hero-1.jpg',
                        'mime_type' => 'image/jpeg',
                        'file_size' => 0,
                        'sort_order' => 0,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    [
                        'section_id' => $credibilityId,
                        'media_key' => 'stay_awhile_logo',
                        'original_path' => '/legacy/assets/Tena-logo-square.jpg',
                        'mime_type' => 'image/jpeg',
                        'file_size' => 0,
                        'sort_order' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                ];
                DB::table('landing_media')->insert($mediaRows);
            }

            // 2. Insert the partners carousel at the end.
            $partnersExists = DB::table('landing_sections')
                ->where('section_key', 'partners')
                ->exists();

            if (! $partnersExists) {
                $partnersSortOrder = (int) (DB::table('landing_sections')->max('sort_order') ?? 0) + 1;

                $partnersId = DB::table('landing_sections')->insertGetId([
                    'section_key' => 'partners',
                    'title' => 'Trusted by hosts and partners across Africa',
                    'subtitle' => '',
                    'bg' => 'white',
                    'sort_order' => $partnersSortOrder,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $partnerList = [
                    ['name' => 'Stay Awhile Rentals', 'url' => 'https://stayawhilerentals.com'],
                    ['name' => 'Airbnb', 'url' => 'https://www.airbnb.com'],
                    ['name' => 'Booking.com', 'url' => 'https://www.booking.com'],
                    ['name' => 'Vrbo', 'url' => 'https://www.vrbo.com'],
                    ['name' => 'Expedia', 'url' => 'https://www.expedia.com'],
                    ['name' => 'Hostaway', 'url' => 'https://www.hostaway.com'],
                ];

                $partnerContentRows = [];
                $partnerMediaRows = [];
                foreach ($partnerList as $i => $partner) {
                    $partnerContentRows[] = [
                        'section_id' => $partnersId,
                        'content_key' => "partners.{$i}.name",
                        'value' => $partner['name'],
                        'type' => 'text',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    $partnerContentRows[] = [
                        'section_id' => $partnersId,
                        'content_key' => "partners.{$i}.url",
                        'value' => $partner['url'],
                        'type' => 'text',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    $partnerMediaRows[] = [
                        'section_id' => $partnersId,
                        'media_key' => "partner_{$i}_logo",
                        'original_path' => '/legacy/assets/Tena-logo-square.jpg',
                        'mime_type' => 'image/jpeg',
                        'file_size' => 0,
                        'sort_order' => $i,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                DB::table('landing_content')->insert($partnerContentRows);
                DB::table('landing_media')->insert($partnerMediaRows);
            }
        });
    }

    public function down(): void
    {
        foreach (['credibility', 'partners'] as $key) {
            DB::table('landing_sections')->where('section_key', $key)->delete();
        }
    }
};
