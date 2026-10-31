<?php

namespace App\Console\Commands;

use App\Models\LandingPage;
use App\Models\LandingSection;
use App\Services\Cms\PageBlueprint;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Moves the live homepage onto the three-audience copy in the blueprint.
 *
 * Live copy belongs to the admin, so this never runs on its own:
 *   --dry-run   lists every field it would change (old -> new) and stops.
 *   (no flag)   saves a backup of the current content, then applies the
 *               copy, switches the homepage sections on and puts them in
 *               blueprint order.
 *   --restore=  puts a backup back exactly as it was.
 *
 * It only touches the homepage sections and the Site-wide header, footer
 * and announcement bar. Uploaded images and other pages are left alone.
 */
class HomepageCopyCommand extends Command
{
    protected $signature = 'tenafi:homepage-copy {--dry-run : Show the changes without saving anything} {--restore= : Restore a backup file from storage/app/cms-backups}';

    protected $description = 'Apply the three-audience homepage copy (with --dry-run diff and a backup first)';

    /** page slug => section keys to update ("*" = every section the blueprint lists for that page). */
    private const TARGETS = [
        'home' => '*',
        LandingPage::SITE => ['header', 'footer', 'announcement', 'whatsapp_button'],
    ];

    /** Sections where only some fields belong to the homepage copy (the rest stay as the admin set them). */
    private const ONLY_FIELDS = [
        'whatsapp_button' => ['menu_greeting', 'menu_home'],
    ];

    public function handle(): int
    {
        if ($file = $this->option('restore')) {
            return $this->restore($file);
        }

        $dryRun = (bool) $this->option('dry-run');
        $plan = $this->plan();

        if (! $plan['changes'] && ! $plan['sections']) {
            $this->info('The homepage already matches the new copy. Nothing to do.');

            return self::SUCCESS;
        }

        foreach ($plan['changes'] as [$where, $key, $old, $new]) {
            $this->line("<comment>[{$where}]</comment> {$key}");
            $this->line('  - '.($old === null ? '(new field)' : $this->short($old)));
            $this->line('  + '.$this->short($new));
        }
        foreach ($plan['sections'] as $line) {
            $this->line("<comment>[section]</comment> {$line}");
        }
        $this->newLine();
        $this->info(count($plan['changes']).' field(s) and '.count($plan['sections']).' section change(s).');

        if ($dryRun) {
            $this->warn('Dry run: nothing was saved. Run without --dry-run to back up and apply.');

            return self::SUCCESS;
        }

        $backup = $this->backup();
        $this->info("Backup saved: storage/app/{$backup}");

        DB::transaction(fn () => $this->apply());
        LandingPage::clearCache();
        $this->info('Applied. To undo: php artisan tenafi:homepage-copy --restore='.basename($backup));

        return self::SUCCESS;
    }

    /**
     * @return array{changes: list<array{0: string, 1: string, 2: ?string, 3: string}>, sections: list<string>}
     */
    private function plan(): array
    {
        $changes = [];
        $sections = [];

        foreach ($this->targets() as [$page, $key, $definition]) {
            $section = LandingSection::where('page_id', $page->id)->where('section_key', $key)->first();
            if (! $section) {
                $sections[] = "{$page->slug}/{$key}: missing (run php artisan migrate first)";

                continue;
            }
            $live = $section->contents()->pluck('value', 'content_key');
            foreach ($this->fields($key, $definition) as $contentKey => $value) {
                $current = $live[$contentKey] ?? null;
                if ($current === null || self::plain($current) !== self::plain($value)) {
                    $changes[] = ["{$page->slug}/{$key}", $contentKey, $current, $value];
                }
            }
            if (! $section->is_active) {
                $sections[] = "{$page->slug}/{$key}: switch on";
            }
        }

        $home = LandingPage::where('slug', 'home')->first();
        if ($home && $this->homeOrder($home) !== $home->sections()->orderBy('sort_order')->orderBy('id')->pluck('section_key')->all()) {
            $sections[] = 'home: reorder to '.implode(', ', array_keys(PageBlueprint::definitions()['home']));
        }

        return ['changes' => $changes, 'sections' => $sections];
    }

    private function apply(): void
    {
        foreach ($this->targets() as [$page, $key, $definition]) {
            $section = LandingSection::where('page_id', $page->id)->where('section_key', $key)->first();
            if (! $section) {
                continue;
            }
            foreach ($this->fields($key, $definition) as $contentKey => $value) {
                $section->contents()->updateOrCreate(['content_key' => $contentKey], [
                    'value' => $value,
                    'type' => str_starts_with($value, '[') ? 'json' : (str_contains($value, '<') ? 'html' : 'text'),
                ]);
            }
            $section->update(['is_active' => true]);
        }

        if ($home = LandingPage::where('slug', 'home')->first()) {
            foreach ($this->homeOrder($home) as $i => $key) {
                LandingSection::where('page_id', $home->id)->where('section_key', $key)->update(['sort_order' => $i + 1]);
            }
        }
    }

    /** @return array<string, string> the section's blueprint fields this command owns */
    private function fields(string $key, array $definition): array
    {
        $fields = PageBlueprint::flatten($definition['content'] ?? []);

        return isset(self::ONLY_FIELDS[$key]) ? array_intersect_key($fields, array_flip(self::ONLY_FIELDS[$key])) : $fields;
    }

    /** Blueprint order first, then any other live homepage sections in their current order. */
    private function homeOrder(LandingPage $home): array
    {
        $live = $home->sections()->orderBy('sort_order')->orderBy('id')->pluck('section_key')->all();
        $wanted = array_values(array_intersect(array_keys(PageBlueprint::definitions()['home']), $live));

        return array_values(array_unique(array_merge($wanted, $live)));
    }

    /** @return list<array{0: LandingPage, 1: string, 2: array}> */
    private function targets(): array
    {
        $blueprint = PageBlueprint::definitions();
        $targets = [];
        foreach (self::TARGETS as $slug => $keys) {
            $page = LandingPage::where('slug', $slug)->first();
            if (! $page) {
                continue;
            }
            foreach ($keys === '*' ? array_keys($blueprint[$slug] ?? []) : $keys as $key) {
                if (isset($blueprint[$slug][$key]) && empty($blueprint[$slug][$key]['keep'])) {
                    $targets[] = [$page, $key, $blueprint[$slug][$key]];
                }
            }
        }

        return $targets;
    }

    private function backup(): string
    {
        $data = [];
        foreach (array_keys(self::TARGETS) as $slug) {
            $page = LandingPage::where('slug', $slug)->first();
            foreach ($page ? $page->sections()->with('contents')->get() : [] as $section) {
                $data[$slug][$section->section_key] = [
                    'is_active' => (bool) $section->is_active,
                    'sort_order' => $section->sort_order,
                    'content' => $section->contents->pluck('value', 'content_key')->all(),
                ];
            }
        }

        $path = 'cms-backups/homepage-copy-'.now()->format('Ymd-His').'.json';
        Storage::disk('local')->put($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return $path;
    }

    private function restore(string $file): int
    {
        $path = 'cms-backups/'.basename($file);
        if (! Storage::disk('local')->exists($path)) {
            $this->error("No backup at storage/app/{$path}");

            return self::FAILURE;
        }

        $data = json_decode(Storage::disk('local')->get($path), true);
        DB::transaction(function () use ($data) {
            foreach ($data as $slug => $sections) {
                $page = LandingPage::where('slug', $slug)->first();
                foreach ($page ? $sections : [] as $key => $saved) {
                    $section = LandingSection::where('page_id', $page->id)->where('section_key', $key)->first();
                    if (! $section) {
                        continue;
                    }
                    $section->update(['is_active' => $saved['is_active'], 'sort_order' => $saved['sort_order']]);
                    // Fields the copy added are removed; every saved field gets its old value back.
                    $section->contents()->whereNotIn('content_key', array_keys($saved['content']))->delete();
                    foreach ($saved['content'] as $contentKey => $value) {
                        $section->contents()->updateOrCreate(['content_key' => $contentKey], ['value' => $value]);
                    }
                }
            }
        });
        LandingPage::clearCache();
        $this->info("Restored storage/app/{$path}.");

        return self::SUCCESS;
    }

    /** The rich-text editor may wrap text in <p> or encode quotes; compare the words. */
    private static function plain(?string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }

    private function short(?string $value): string
    {
        return Str::limit(str_replace("\n", ' / ', (string) $value), 140);
    }
}
