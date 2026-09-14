<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Re-insert any landing_media rows that a landing component expects but
 * that no longer exist in the database (typically because they were
 * deleted from the CMS by accident).
 *
 * Guardrails:
 *   - Reads the intended layout from config/landing_schema.php.
 *   - Only INSERTs missing rows. Never updates or overwrites existing
 *     rows, so admin-uploaded artwork is never touched.
 *   - Idempotent: running twice produces no additional changes.
 *   - Dynamic slots are derived from the section's own content_keys
 *     (e.g. one `partner_{i}_logo` per `partners.{i}.name` row), so we
 *     only re-add slots the current data actually references.
 */
return new class extends Migration
{
    public function up(): void
    {
        $schema = require config_path('landing_schema.php');
        if (! is_array($schema)) {
            return;
        }

        $now = now();

        foreach ($schema as $sectionKey => $config) {
            $section = DB::table('landing_sections')
                ->where('section_key', $sectionKey)
                ->first(['id']);

            if (! $section) {
                // If a section is missing entirely, let dedicated section
                // migrations handle it — we only heal media rows.
                continue;
            }

            $existingMediaKeys = DB::table('landing_media')
                ->where('section_id', $section->id)
                ->pluck('media_key')
                ->all();
            $existingMediaKeys = array_flip($existingMediaKeys);

            $sortOrder = (int) DB::table('landing_media')
                ->where('section_id', $section->id)
                ->max('sort_order') + 1;

            $inserts = [];

            // Static slots — from the schema.
            foreach (($config['static'] ?? []) as $mediaKey => $slot) {
                if (isset($existingMediaKeys[$mediaKey])) {
                    continue;
                }
                $inserts[] = [
                    'section_id' => $section->id,
                    'media_key' => $mediaKey,
                    'original_path' => $slot['original_path'] ?? '',
                    'mime_type' => $slot['mime_type'] ?? 'image/jpeg',
                    'file_size' => 0,
                    'sort_order' => $sortOrder++,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // Dynamic slots — one per matching content_key index.
            foreach (($config['dynamic'] ?? []) as $dyn) {
                $arrayName = $dyn['array'] ?? null;
                $keyPattern = $dyn['key'] ?? null;
                if (! $arrayName || ! $keyPattern) {
                    continue;
                }

                $indices = $this->collectArrayIndices($section->id, $arrayName);
                foreach ($indices as $i) {
                    $mediaKey = str_replace('{i}', (string) $i, $keyPattern);
                    if (isset($existingMediaKeys[$mediaKey])) {
                        continue;
                    }
                    $inserts[] = [
                        'section_id' => $section->id,
                        'media_key' => $mediaKey,
                        'original_path' => $dyn['default'] ?? '',
                        'mime_type' => $dyn['mime_type'] ?? 'image/jpeg',
                        'file_size' => 0,
                        'sort_order' => $sortOrder++,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            if (! empty($inserts)) {
                DB::table('landing_media')->insert($inserts);
            }
        }
    }

    public function down(): void
    {
        // Non-reversible by design — we do not know which rows were
        // inserted by the heal pass vs. by earlier migrations. Leaving
        // this as a no-op is the safe default.
    }

    /**
     * Return the unique indices present in this section's content for a
     * given array prefix. For content_key `partners.0.name`,
     * `partners.2.url`, etc., returns [0, 2].
     */
    private function collectArrayIndices(int $sectionId, string $arrayName): array
    {
        $rows = DB::table('landing_content')
            ->where('section_id', $sectionId)
            ->where('content_key', 'like', $arrayName.'.%')
            ->pluck('content_key');

        $indices = [];
        foreach ($rows as $key) {
            if (preg_match('/^'.preg_quote($arrayName, '/').'\\.(\\d+)\\./', $key, $m)) {
                $indices[(int) $m[1]] = true;
            }
        }

        $out = array_keys($indices);
        sort($out);

        return $out;
    }
};
