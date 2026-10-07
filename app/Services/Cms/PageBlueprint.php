<?php

namespace App\Services\Cms;

use App\Models\LandingPage;
use App\Models\LandingSection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Applies database/blueprints/public_pages.php to the CMS tables.
 *
 * Default mode is additive: missing pages, sections, content keys and media
 * slots are created, and anything an admin already edited is left alone.
 *
 * Relaunch mode (used once by the TenaFi migration) also replaces the text
 * content of every blueprint section, re-orders sections to match the
 * blueprint, disables sections the blueprint no longer lists, and swaps
 * seeded /legacy/ placeholder images. Media an admin uploaded is kept.
 */
class PageBlueprint
{
    public static function definitions(): array
    {
        return require database_path('blueprints/public_pages.php');
    }

    public static function sync(bool $relaunch = false): void
    {
        DB::transaction(function () use ($relaunch) {
            foreach (static::definitions() as $slug => $sections) {
                $page = LandingPage::firstOrCreate(
                    ['slug' => $slug],
                    ['name' => ucfirst($slug), 'is_routable' => $slug !== LandingPage::SITE]
                );

                $order = 0;
                foreach ($sections as $key => $definition) {
                    static::syncSection($page, $key, $definition, ++$order, $relaunch);
                }

                if ($relaunch) {
                    LandingSection::where('page_id', $page->id)
                        ->whereNotIn('section_key', array_keys($sections))
                        ->update(['is_active' => false]);
                }
            }
        });

        LandingPage::clearCache();
    }

    /**
     * Flip a feature's "Coming soon" badge (Site-wide → Feature status) once
     * it ships. For data migrations; admins can still change it afterwards.
     */
    public static function setFeatureStatus(string $feature, string $status): void
    {
        $section = LandingSection::where('section_key', 'feature_status')
            ->whereHas('page', fn ($q) => $q->where('slug', LandingPage::SITE))
            ->first();

        $keyRow = $section?->contents()->where('content_key', 'like', 'items.%.key')->where('value', $feature)->first();

        if ($keyRow) {
            $statusKey = preg_replace('/\.key$/', '.status', $keyRow->content_key);
            $section->contents()->where('content_key', $statusKey)->update(['value' => $status]);
            LandingPage::clearCache();
        }
    }

    private static function syncSection(LandingPage $page, string $key, array $definition, int $order, bool $relaunch): void
    {
        $section = LandingSection::where('page_id', $page->id)->where('section_key', $key)->first();

        if (! empty($definition['keep'])) {
            // Placeholder: keep whatever content exists, just put it in order.
            if ($section && $relaunch) {
                $section->update(['sort_order' => $order, 'is_active' => true]);
            }

            return;
        }

        $meta = [
            'title' => $definition['title'] ?? null,
            'bg' => $definition['bg'] ?? 'white',
            'is_active' => $definition['is_active'] ?? true,
            'sort_order' => $order,
        ];

        $isNew = ! $section;
        if ($isNew) {
            $section = LandingSection::create(['page_id' => $page->id, 'section_key' => $key] + $meta);
        } elseif ($relaunch) {
            $section->update($meta);
            $section->contents()->delete();
        }

        $existingKeys = $section->contents()->pluck('content_key')->all();
        $now = now();
        $rows = [];
        foreach (static::flatten($definition['content'] ?? []) as $contentKey => $value) {
            if (in_array($contentKey, $existingKeys, true)) {
                continue;
            }
            $rows[] = [
                'section_id' => $section->id,
                'content_key' => $contentKey,
                'value' => $value,
                'type' => str_starts_with($value, '[') ? 'json' : (str_contains($value, '<') ? 'html' : 'text'),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        if ($rows) {
            DB::table('landing_content')->insert($rows);
        }

        foreach ($definition['media'] ?? [] as $mediaKey => $path) {
            $current = $section->media()->where('media_key', $mediaKey)->first();
            $isPlaceholder = $current && str_starts_with($current->original_path, '/legacy/');

            if ($current && ! ($relaunch && $isPlaceholder)) {
                continue;
            }

            $section->media()->updateOrCreate(['media_key' => $mediaKey], [
                'original_path' => $path,
                'optimized_path' => null,
                'thumbnail_path' => null,
                'mime_type' => static::mimeFor($path),
                'file_size' => 0,
                'sort_order' => 0,
            ]);
        }
    }

    /**
     * Flatten blueprint content into CMS content keys.
     *
     * ['steps' => [['title' => 'A']]]       => ['steps.0.title' => 'A']
     * ['sections' => [['features' => [..]]]] => ['sections.0.features' => '[..json..]']
     *
     * @return array<string, string>
     */
    public static function flatten(array $content): array
    {
        $flat = [];
        foreach ($content as $key => $value) {
            if (is_array($value) && array_is_list($value) && $value && is_array($value[0])) {
                foreach ($value as $i => $row) {
                    foreach ($row as $field => $fieldValue) {
                        $flat["{$key}.{$i}.{$field}"] = static::scalar($fieldValue);
                    }
                }

                continue;
            }
            $flat[$key] = static::scalar($value);
        }

        return $flat;
    }

    private static function scalar(mixed $value): string
    {
        return match (true) {
            is_array($value) => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            is_bool($value) => $value ? '1' : '0',
            default => (string) $value,
        };
    }

    private static function mimeFor(string $path): string
    {
        return Arr::get([
            'png' => 'image/png',
            'svg' => 'image/svg+xml',
            'webp' => 'image/webp',
            'mp4' => 'video/mp4',
        ], strtolower(pathinfo($path, PATHINFO_EXTENSION)), 'image/jpeg');
    }
}
