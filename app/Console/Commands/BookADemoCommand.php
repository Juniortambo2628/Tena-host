<?php

namespace App\Console\Commands;

use App\Models\LandingContent;
use App\Models\LandingPage;
use App\Models\LandingSection;
use App\Models\Setting;
use App\Services\Cms\PageBlueprint;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * The free trial is over: new customers book a demo (a call, a video call
 * or a visit). This moves the live public pages over, on every page and its
 * v2 copy:
 *  - the slot counter is switched off wherever a section shows it;
 *  - known trial sentences are taken out of longer texts (footnotes,
 *    subtitles) and the rest of the admin's wording is kept;
 *  - any other field that still mentions the trial gets the blueprint's
 *    "Book a demo" wording for it;
 *  - the rentals and business sign-up forms ask how they'd like their demo.
 * Then it lists anything that still mentions the trial (fields with no
 * blueprint wording, email settings), for an admin to edit by hand.
 *
 *   --dry-run   lists every change and stops.
 *   (no flag)   saves a backup, then applies.
 *   --restore=  puts a backup back.
 */
class BookADemoCommand extends Command
{
    protected $signature = 'tenafi:book-a-demo {--dry-run : Show the changes without saving anything} {--restore= : Restore a backup file from storage/app/cms-backups}';

    protected $description = 'Replace the free trial with "Book a demo" across the public pages (with --dry-run and a backup first)';

    /** Live page => the blueprint page its wording comes from (v2 pages are copies). */
    private const BLUEPRINT_PAGE = [
        'home' => 'home', 'v2' => 'home',
        'hosts' => 'hosts', 'v2-hosts' => 'hosts',
        'business' => 'business', 'v2-business' => 'business',
        'events' => 'events', 'v2-events' => 'events',
        LandingPage::SITE => LandingPage::SITE,
    ];

    /** Words that mean a field still talks about the free trial. */
    private const TRIAL = '/free trial|after the trial|trial slot|book (a|your) slot|slots? (taken|left)|all of december|free for december|the whole of december|december is on us|16 november|no payment to book/i';

    /** Trial sentences inside longer texts, and what replaces them. */
    private const SENTENCES = [
        '/Free trial: installs from 16 November, and the rest of November and all of December are free\.\s*Then month to month;/u' => 'Month to month;',
        '/Free for December, then month to month\./u' => 'Month to month.',
        // The rentals pricing subtitle (also on the homepage and /pricing).
        '/\s*Free trial from 1st December until 4 January 2027[^.]*\./u' => '',
    ];

    /**
     * Fields whose blueprint wording was rewritten for the demo, by section
     * type ("*" = any). Only these take the blueprint's wording; anything else
     * that mentions the trial is listed for a person to edit.
     */
    private const DEMO_FIELDS = [
        '*' => ['cta_primary', 'cta_label', 'callback_note', 'callback_link_label', 'price_line', 'ticks', 'submit_label', 'success_message', 'next_steps', 'menu_label', 'menu_description'],
        'cta_banner' => ['badge', 'title', 'body', 'buttons_title', 'pricing_title', 'buttons.*.label', 'buttons.*.href'],
        'signup' => ['badge', 'subtitle'],
        'announcement' => ['text', 'text_without_slots', 'link_label'],
        'footer' => ['groups.*.links'],
        'whatsapp_button' => ['message_*', 'menu_*'],
    ];

    private const FAQ_QUESTION = 'What happens in a demo?';

    private const FAQ_ANSWER = 'On a call, a video call or a visit to your place, we show you TenaFi working, look at your space and recommend the right device and plan. There’s no commitment. After that it’s month to month, or cancel with 30 days’ notice.';

    public function handle(): int
    {
        if ($file = $this->option('restore')) {
            return $this->restore($file);
        }

        $changes = $this->plan();
        $byId = collect($changes)->keyBy('id');

        foreach ($changes as $change) {
            $this->line("<comment>[{$change['where']}]</comment> {$change['key']}");
            if (isset($change['note'])) {
                $this->line('  + '.$change['note']);

                continue;
            }
            $this->line('  - '.$this->short($change['old']));
            $this->line('  + '.$this->short($change['new']));
        }

        $leftovers = $this->leftovers($byId->map(fn ($c) => $c['new'])->all());
        foreach ($leftovers as $line) {
            $this->line("<fg=red>[still mentions the trial]</> {$line}");
        }

        $this->newLine();
        if (! $changes) {
            $this->info('Nothing to change: the public pages already say "Book a demo".'.($leftovers ? ' Edit the fields above by hand.' : ''));

            return self::SUCCESS;
        }
        $this->info(count($changes).' field(s) to change.'.($leftovers ? ' '.count($leftovers).' to edit by hand afterwards (listed above).' : ''));

        if ($this->option('dry-run')) {
            $this->warn('Dry run: nothing was saved. Run without --dry-run to back up and apply.');

            return self::SUCCESS;
        }

        $backup = $this->backup($changes);
        $this->info("Backup saved: storage/app/{$backup}");

        DB::transaction(function () use ($changes) {
            foreach ($changes as $change) {
                LandingContent::whereKey($change['id'])->update(['value' => $change['new']]);
            }
        });
        LandingPage::clearCache();
        $this->info('Applied. To undo: php artisan tenafi:book-a-demo --restore='.basename($backup));

        return self::SUCCESS;
    }

    /** @return list<array{id: int, where: string, key: string, old: string, new: string, note?: string}> */
    private function plan(): array
    {
        $blueprint = PageBlueprint::definitions();
        $changes = [];

        foreach (LandingPage::with('sections.contents')->get() as $page) {
            $bpPage = self::BLUEPRINT_PAGE[$page->slug] ?? null;
            foreach ($page->sections as $section) {
                $defaults = $bpPage && isset($blueprint[$bpPage][$section->section_key]['content'])
                    ? PageBlueprint::flatten($blueprint[$bpPage][$section->section_key]['content'])
                    : [];
                $content = $section->contents->pluck('value', 'content_key');

                $type = explode('__', $section->section_key)[0];
                foreach ($section->contents as $row) {
                    $new = $this->newValue($type, $row->content_key, (string) $row->value, $defaults, $content->all());
                    if ($new !== null && $new !== $row->value) {
                        $changes[] = ['id' => $row->id, 'where' => "{$page->slug}/{$section->section_key}", 'key' => $row->content_key, 'old' => (string) $row->value, 'new' => $new];
                    }
                }

                if ($section->section_key === 'signup' && $change = $this->demoQuestion($page, $section, $blueprint[$bpPage]['signup'] ?? null)) {
                    $changes[] = $change;
                }
            }
        }

        return $changes;
    }

    /** The field's "Book a demo" value, or null to leave it as it is. */
    private function newValue(string $type, string $key, string $value, array $defaults, array $content): ?string
    {
        // The slot counter goes everywhere.
        if ($key === 'show_slots') {
            return filter_var(strip_tags($value), FILTER_VALIDATE_BOOLEAN) ? '0' : null;
        }

        // An FAQ about the trial becomes the demo question (its answer follows it).
        if (preg_match('/^items\.(\d+)\.question$/', $key) && preg_match(self::TRIAL, $value)) {
            return self::FAQ_QUESTION;
        }
        if (preg_match('/^items\.(\d+)\.answer$/', $key, $m) && preg_match(self::TRIAL, (string) ($content["items.{$m[1]}.question"] ?? ''))) {
            return self::FAQ_ANSWER;
        }

        if (! preg_match(self::TRIAL, $value)) {
            return null;
        }

        // Trial sentences out of longer texts, keeping the rest.
        $trimmed = preg_replace(array_keys(self::SENTENCES), array_values(self::SENTENCES), $value);
        if (! preg_match(self::TRIAL, $trimmed)) {
            return $trimmed;
        }

        // Otherwise the blueprint's demo wording, for the fields rewritten for it.
        $rewritten = array_merge(self::DEMO_FIELDS['*'], self::DEMO_FIELDS[$type] ?? []);
        $default = trim((string) ($defaults[$key] ?? ''));

        return $default !== '' && Str::is($rewritten, $key) && ! preg_match(self::TRIAL, $default) ? $defaults[$key] : null;
    }

    /** "How would you like your demo?" on the details step of a rentals or business form. */
    private function demoQuestion(LandingPage $page, LandingSection $section, ?array $definition): ?array
    {
        $field = collect($definition['content']['steps'] ?? [])->flatMap(fn ($s) => $s['fields'] ?? [])->firstWhere('key', 'demoType');
        $row = $section->contents->firstWhere('content_key', 'steps.0.fields');
        if (! $field || ! $row) {
            return null;
        }

        $fields = json_decode((string) $row->value, true) ?: [];
        if (collect($fields)->contains('key', 'demoType')) {
            return null;
        }
        $fields[] = $field;

        return [
            'id' => $row->id, 'where' => "{$page->slug}/signup", 'key' => 'steps.0.fields', 'old' => (string) $row->value,
            'new' => json_encode($fields, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'note' => 'new question on the details step: '.$field['label'],
        ];
    }

    /**
     * Fields and email settings that will still mention the trial after the
     * planned changes, to edit by hand.
     *
     * @param  array<int, string>  $planned  content id => new value
     * @return list<string>
     */
    private function leftovers(array $planned): array
    {
        $lines = [];
        foreach (LandingContent::with('section.page')->get() as $row) {
            $value = (string) ($planned[$row->id] ?? $row->value);
            // The old counter's own wording is never shown now.
            if ($row->section?->section_key === 'trial_slots' || ! preg_match(self::TRIAL, $value)) {
                continue;
            }
            $lines[] = "{$row->section?->page?->slug}/{$row->section?->section_key} {$row->content_key}: ".$this->short($value);
        }

        foreach (Setting::query()->get(['key', 'value']) as $setting) {
            if (is_string($setting->value) && preg_match(self::TRIAL, $setting->value)) {
                $lines[] = "Admin -> Settings {$setting->key}: ".$this->short($setting->value);
            }
        }

        return $lines;
    }

    private function backup(array $changes): string
    {
        $path = 'cms-backups/book-a-demo-'.now()->format('Ymd-His').'.json';
        Storage::disk('local')->put($path, json_encode([
            'contents' => collect($changes)->map(fn ($c) => ['id' => $c['id'], 'value' => $c['old']])->values(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

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
            foreach ($data['contents'] ?? [] as $row) {
                LandingContent::whereKey($row['id'])->update(['value' => $row['value']]);
            }
        });
        LandingPage::clearCache();
        $this->info("Restored storage/app/{$path}.");

        return self::SUCCESS;
    }

    private function short(?string $value): string
    {
        return Str::limit(str_replace("\n", ' / ', strip_tags((string) $value)), 150);
    }
}
