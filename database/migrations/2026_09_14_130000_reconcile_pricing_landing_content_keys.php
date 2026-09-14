<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $sectionId = DB::table('landing_sections')
            ->where('section_key', 'pricing')
            ->value('id');

        if (! $sectionId) {
            return;
        }

        // Drop keys the redesigned pricing section no longer reads.
        DB::table('landing_content')
            ->where('section_id', $sectionId)
            ->whereIn('content_key', ['cta_text', 'footer_text', 'footer_email'])
            ->delete();

        // Add the new keys the redesigned pricing section reads, without
        // overwriting anything an admin has already customised.
        $newKeys = [
            [
                'content_key' => 'cta_headline',
                'value' => 'Join the first 100 hosts shaping Tena.',
                'type' => 'text',
            ],
            [
                'content_key' => 'cta_intro',
                'value' => "We're inviting our first 100 Superhosts into the Founding Host Program — priority access before public launch and a direct line to the team building Tena.",
                'type' => 'text',
            ],
            [
                'content_key' => 'cta_closing',
                'value' => 'Applications are open now. Once all 100 spots are filled, the program closes.',
                'type' => 'text',
            ],
        ];

        foreach ($newKeys as $row) {
            DB::table('landing_content')->updateOrInsert(
                ['section_id' => $sectionId, 'content_key' => $row['content_key']],
                array_merge($row, ['section_id' => $sectionId, 'created_at' => now(), 'updated_at' => now()])
            );
        }
    }

    public function down(): void
    {
        $sectionId = DB::table('landing_sections')
            ->where('section_key', 'pricing')
            ->value('id');

        if (! $sectionId) {
            return;
        }

        DB::table('landing_content')
            ->where('section_id', $sectionId)
            ->whereIn('content_key', ['cta_headline', 'cta_intro', 'cta_closing'])
            ->delete();
    }
};
