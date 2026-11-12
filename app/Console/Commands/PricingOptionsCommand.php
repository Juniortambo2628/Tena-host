<?php

namespace App\Console\Commands;

use App\Models\LandingContent;
use App\Models\LandingPage;
use App\Models\LandingSection;
use App\Services\Cms\PageBlueprint;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Gives the live /hosts and /business pricing (and their v2 copies) the two
 * ways to pay, without touching what Glen has written:
 *  - only NEW pricing fields (pay_*, devices_*, example_*, bigger_*,
 *    vat_note, monthly_price_line, device_price_line) are filled, and only
 *    where they are missing or empty; plan names, prices, features and
 *    footnotes are never changed;
 *  - the device FAQs are added where their question isn't there yet;
 *  - the homepage gets its device line where it has none;
 *  - a plan's features that were saved as HTML (<br>, <p>, ...) or squashed
 *    onto one line become one item per line, the latter only when the line
 *    is exactly the default list joined with spaces.
 * It ends with the admin checklist: live fields to read and edit by hand.
 *
 *   --dry-run   lists every change and the checklist, and stops.
 *   (no flag)   saves a backup, then applies.
 *   --restore=  puts a backup back.
 */
class PricingOptionsCommand extends Command
{
    protected $signature = 'tenafi:pricing-options {--dry-run : Show the changes without saving anything} {--restore= : Restore a backup file from storage/app/cms-backups}';

    protected $description = 'Add the two ways to pay, TenaFi devices and device FAQs to the live pricing (/hosts, /business and the homepage) (fills only new, empty fields; --dry-run first)';

    /** Live page => the blueprint page its defaults come from. */
    private const PAGES = ['hosts' => 'hosts', 'v2-hosts' => 'hosts', 'business' => 'business', 'v2-business' => 'business', 'v2' => 'hosts'];

    /** Pages that get the pricing words only: the homepage's plans are the rentals plans, and its FAQ stays as it is. */
    private const PRICING_ONLY = ['v2'];

    private const HOME_PAGES = ['home', 'v2'];

    /** The pricing fields this change adds (see $payOptions in the blueprint). */
    private const NEW_FIELDS = [
        'pay_label', 'pay_buy_label', 'pay_buy_badge', 'pay_buy_text', 'pay_buy_terms',
        'pay_monthly_label', 'pay_monthly_badge', 'pay_monthly_text', 'pay_monthly_terms',
        'monthly_price_line', 'devices_title', 'device_price_line', 'devices_note',
        'example_label', 'example_plan', 'example_device', 'example_text',
        'bigger_title', 'bigger_spaces', 'bigger_cta_label', 'bigger_cta_href', 'vat_note',
        // "Your first 3 months are free" (guarantee_text stays empty until Glen decides).
        'free_months', 'free_banner_title', 'free_banner_text', 'free_design_cta', 'free_design_text',
        'free_buy_text', 'free_monthly_text', 'free_cta_label', 'free_first_line', 'free_buy_then', 'free_buy_device',
        'free_monthly_first', 'free_monthly_then', 'free_example_text',
    ];

    /** FAQs this command adds where their question isn't there yet (blueprint $deviceFaqs and $freeFaqs). */
    private const FAQ_QUESTIONS = [
        'Do I have to buy the device?', 'Which device do I need?', 'What if I leave?', 'Do I need more than one device?',
        'What’s free?', 'Can I see it before I pay?', 'What happens after 3 months?',
    ];

    /** @var list<array{section_id: int, key: string, where: string, old: ?string, new: string}> */
    private array $changes = [];

    /** @var list<string> */
    private array $checklist = [];

    public function handle(): int
    {
        $this->changes = $this->checklist = [];

        if ($file = $this->option('restore')) {
            return $this->restore($file);
        }

        $this->plan();

        foreach ($this->changes as $c) {
            $this->line("<comment>[{$c['where']}]</comment> {$c['key']}");
            $this->line('  - '.($c['old'] === null ? '(new field)' : ($c['old'] === '' ? '(empty)' : $this->short($c['old']))));
            $this->line('  + '.$this->short($c['new']));
        }
        $this->newLine();
        $this->line('<options=bold>Admin checklist: check or edit these by hand in Admin -> Public Pages</>');
        foreach ($this->checklist as $item) {
            $this->line("  [ ] {$item}");
        }
        $this->newLine();

        if (! $this->changes) {
            $this->info('Nothing to fill: the live pricing already has the two ways to pay.');

            return self::SUCCESS;
        }
        $this->info(count($this->changes).' field(s) to fill.');

        if ($this->option('dry-run')) {
            $this->warn('Dry run: nothing was saved. Run without --dry-run to back up and apply.');

            return self::SUCCESS;
        }

        $backup = $this->backup();
        $this->info("Backup saved: storage/app/{$backup}");
        DB::transaction(function () {
            foreach ($this->changes as $c) {
                LandingContent::updateOrCreate(['section_id' => $c['section_id'], 'content_key' => $c['key']], [
                    'value' => $c['new'],
                    'type' => str_contains($c['new'], '<') ? 'html' : 'text',
                ]);
            }
        });
        LandingPage::clearCache();
        $this->info('Applied. To undo: php artisan tenafi:pricing-options --restore='.basename($backup));

        return self::SUCCESS;
    }

    private function plan(): void
    {
        $blueprint = PageBlueprint::definitions();

        foreach (self::PAGES as $slug => $bp) {
            $defaults = PageBlueprint::flatten($blueprint[$bp]['pricing']['content'] ?? []);
            if ($pricing = $this->section($slug, 'pricing')) {
                $live = $pricing->contents->pluck('value', 'content_key');
                foreach (self::NEW_FIELDS as $key) {
                    if (array_key_exists($key, $defaults) && trim(strip_tags((string) ($live[$key] ?? ''))) === '' && $defaults[$key] !== '') {
                        $this->change($pricing, "{$slug}/pricing", $key, $live[$key] ?? null, $defaults[$key]);
                    }
                }
                $this->features($pricing, $slug, $live->all(), $blueprint[$bp]['pricing']['content']['plans'] ?? []);
                $this->checkPricing($slug, $live->all());
            }

            if (! in_array($slug, self::PRICING_ONLY, true) && $faq = $this->section($slug, 'faq')) {
                $this->faqs($faq, $slug, $blueprint[$bp]['faq']['content']['items'] ?? []);
            }
        }

        // The homepage: "Plus a TenaFi device from ..." and "3 months free. Then from ..." by the price.
        foreach (self::HOME_PAGES as $slug) {
            foreach (['hero', 'cta_banner__founding'] as $key) {
                $section = $this->section($slug, $key);
                foreach (['device_line', 'free_price_line'] as $field) {
                    $default = $blueprint['home'][$key]['content'][$field] ?? '';
                    $now = $section?->contents->firstWhere('content_key', $field)?->value;
                    if ($section && $default !== '' && trim((string) $now) === '') {
                        $this->change($section, "{$slug}/{$key}", $field, $now, $default);
                    }
                }
            }
        }

        $this->freeChecklist($blueprint);

        $this->checklist[] = 'VAT: are prices incl. or excl. 16% VAT? Then fill vat_note on each pricing section (empty = not shown). [open decision]';
        $this->checklist[] = 'Device prices live once, on Devices & pricing -> devices (/pricing). TenaFi Outdoor KES 48,000 may become ~52,000: change items.2.price_kes there. [open decision]';
        $this->checklist[] = 'Pay monthly: does the client own the device after month 12, or does TenaFi keep it? The footnotes and /pricing say "it’s yours" (Glen’s earlier answer); confirm. [open decision]';
        $this->checklist[] = 'Extra devices: one-off purchases, or does each carry a plan? [open decision]';
        $this->checklist[] = 'Multi-unit discount: does it start at 5 or 10 units? /hosts FAQ "I manage lots of units." and the sign-up discount say 10. [open decision]';
    }

    /** Live wording to read and edit by hand for the free months; nothing here is changed by the command. */
    private function freeChecklist(array $blueprint): void
    {
        foreach (['hosts', 'v2-hosts', 'business', 'v2-business'] as $slug) {
            $live = $this->section($slug, 'pricing')?->contents->pluck('value', 'content_key')->all() ?? [];
            if (! $live) {
                continue;
            }
            $bp = str_contains($slug, 'hosts') ? 'hosts' : 'business';
            $footnote = $blueprint[$bp]['pricing']['content']['footnote'] ?? '';
            if (trim(strip_tags((string) ($live['footnote'] ?? ''))) !== trim($footnote)) {
                $this->checklist[] = "{$slug} pricing footnote: suggested wording for the free months: “".$this->short($footnote).'” Now: '.$this->short($live['footnote'] ?? '');
            }
            if ($bp === 'hosts' && ! str_contains((string) ($live['subtitle'] ?? ''), 'first 3 months are free')) {
                $this->checklist[] = "{$slug} pricing paragraph (subtitle): add “Pay once for your TenaFi device and your first 3 months are free. Installations from Monday 16 November, in time for the December high season.” Now: ".$this->short($live['subtitle'] ?? '');
            }
            if (str_contains((string) ($live['pay_buy_terms'] ?? ''), '2 months free')) {
                $this->checklist[] = "{$slug} pricing, Buy the device terms: yearly billing starts after the free months; say so if it should be explicit (“Pay yearly and get 2 months free”).";
            }
        }

        // "Free one-month trial for December" and similar left in live words.
        LandingSection::with(['contents', 'page'])->whereHas('page', fn ($q) => $q->whereIn('slug', ['home', 'v2', 'hosts', 'v2-hosts', 'business', 'v2-business', 'pricing']))
            ->get()->each(function (LandingSection $section) {
                foreach ($section->contents as $content) {
                    if (preg_match('/one-month trial|free trial|trial for december/i', strip_tags((string) $content->value))) {
                        $this->checklist[] = "{$section->page->slug}/{$section->section_key} {$content->content_key} still mentions a trial: replace it with the free months. Now: ".$this->short($content->value);
                    }
                }
            });

        $this->checklist[] = 'The FAQs “What’s free?” and “What happens after 3 months?” and the pricing paragraph say “3 months”: edit them if free_months changes.';
        $this->checklist[] = '30-day money-back guarantee: fill guarantee_text on each pricing section (planned: “30-day money-back on the device.”) to show it under the banner. [open decision]';
        $this->checklist[] = 'Growth: does it also get the free months, or Starter-level features while they last? The offer now applies to every plan. [open decision]';
    }

    /** Each plan's features as one item per line. */
    private function features(LandingSection $section, string $slug, array $live, array $defaultPlans): void
    {
        $defaults = collect($defaultPlans)->keyBy('id');
        foreach ($live as $key => $value) {
            if (! preg_match('/^plans\.(\d+)\.features$/', $key, $m)) {
                continue;
            }
            $value = (string) $value;
            $lines = static::lines($value);
            $plan = trim(strip_tags((string) ($live["plans.{$m[1]}.id"] ?? $live["plans.{$m[1]}.label"] ?? $m[1])));

            // Saved with tabs or runs of spaces between the items (pasted from a document or a phone).
            if (count($lines) === 1 && preg_match('/\t|[ \x{00A0}]{2,}/u', $value)) {
                $split = array_values(array_filter(array_map('trim', preg_split('/\t+|[ \x{00A0}]{2,}/u', $lines[0] ?? ''))));
                if (count($split) > 1) {
                    $this->change($section, "{$slug}/pricing", $key, $value, implode("\n", $split));

                    continue;
                }
            }

            if (preg_match('/<br|<\/p>|<\/div>|<\/li>|&lt;br|\x{2028}|\x{2029}|\x{0085}/iu', $value)) {
                $this->change($section, "{$slug}/pricing", $key, $value, implode("\n", $lines));

                continue;
            }

            // Squashed onto one line: fix only when it is exactly the default list joined with spaces.
            if (count($lines) === 1 && ($default = $defaults->get(strtolower($plan))['features'] ?? null)) {
                $defaultLines = static::lines($default);
                $squash = fn (array $l) => preg_replace('/\s+/u', ' ', trim(implode(' ', $l)));
                if (count($defaultLines) > 1 && $squash($lines) === $squash($defaultLines)) {
                    $this->change($section, "{$slug}/pricing", $key, $value, implode("\n", $defaultLines));

                    continue;
                }
                // The value as stored (escaped), so a hidden separator shows if there is one.
                $this->checklist[] = "{$slug} pricing, plan “{$plan}” features are on one line: open Pricing -> Plans and put one item per line. Stored as: ".json_encode(mb_substr($value, 0, 160), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
        }
    }

    /** Lines of a list field saved in any format (\n, \r\n, <br>, </p>, </div>, </li>, &lt;br&gt;, Unicode line separators). */
    public static function lines(string $text): array
    {
        $text = preg_replace(['/&lt;br\s*\/?&gt;/i', '/<br\s*\/?>|<\/p>|<\/div>|<\/li>/i'], "\n", $text);

        return array_values(array_filter(array_map(
            fn ($line) => trim(html_entity_decode(strip_tags($line), ENT_QUOTES | ENT_HTML5, 'UTF-8')),
            preg_split('/\r\n|\r|\n|\x{2028}|\x{2029}|\x{0085}/u', $text),
        ), fn ($line) => $line !== ''));
    }

    /** The device FAQs, added at the end where their question isn't there yet. */
    private function faqs(LandingSection $faq, string $slug, array $defaults): void
    {
        $live = $faq->contents->pluck('value', 'content_key');
        $same = fn ($q) => mb_strtolower(str_replace(['’', '‘'], "'", trim(html_entity_decode(strip_tags((string) $q), ENT_QUOTES | ENT_HTML5, 'UTF-8'))));
        $questions = $live->filter(fn ($v, $k) => preg_match('/^items\.\d+\.question$/', $k))->map($same);
        $next = $questions->keys()->map(fn ($k) => (int) explode('.', $k)[1])->max();
        $next = $next === null ? 0 : $next + 1;

        foreach (self::FAQ_QUESTIONS as $question) {
            $item = collect($defaults)->firstWhere('question', $question);
            if (! $item || $questions->contains($same($question))) {
                continue;
            }
            $this->change($faq, "{$slug}/faq", "items.{$next}.question", null, $item['question']);
            $this->change($faq, "{$slug}/faq", "items.{$next}.answer", null, $item['answer']);
            $next++;
        }

        foreach ($live as $key => $value) {
            if (preg_match('/^items\.(\d+)\.question$/', $key, $m) && stripos((string) $value, 'new internet') !== false) {
                $answer = (string) ($live["items.{$m[1]}.answer"] ?? '');
                if (stripos($answer, 'one cable') === false) {
                    $this->checklist[] = "{$slug} FAQ “".trim(strip_tags($value)).'”: say the device connects to the router with one cable. Now: '.$this->short($answer);
                }
            }
        }
    }

    /** Footnotes and wording to read once the switch is on. */
    private function checkPricing(string $slug, array $live): void
    {
        $footnote = (string) ($live['footnote'] ?? '');
        if (stripos($footnote, '2 months free') !== false) {
            $this->checklist[] = "{$slug} pricing footnote: “Pay yearly and get 2 months free” is now on Buy the device only (it’s in that option’s terms). Edit the footnote if it reads as applying to both. Now: ".$this->short($footnote);
        }
        if (preg_match('/free trial|december|16 november/i', $footnote.' '.($live['subtitle'] ?? ''))) {
            $this->checklist[] = "{$slug} pricing still mentions the free trial: run tenafi:book-a-demo (PR #88) or edit the subtitle and footnote.";
        }
    }

    private function section(string $slug, string $key): ?LandingSection
    {
        return LandingSection::with('contents')->where('section_key', $key)->whereHas('page', fn ($q) => $q->where('slug', $slug))->first();
    }

    private function change(LandingSection $section, string $where, string $key, ?string $old, string $new): void
    {
        $this->changes[] = ['section_id' => $section->id, 'key' => $key, 'where' => $where, 'old' => $old, 'new' => $new];
    }

    private function backup(): string
    {
        $path = 'cms-backups/pricing-options-'.now()->format('Ymd-His').'.json';
        Storage::disk('local')->put($path, json_encode([
            'contents' => collect($this->changes)->map(fn ($c) => ['section_id' => $c['section_id'], 'key' => $c['key'], 'value' => $c['old']])->values(),
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
                $query = LandingContent::where('section_id', $row['section_id'])->where('content_key', $row['key']);
                // Fields this command added are removed; changed ones get their old value back.
                $row['value'] === null ? $query->delete() : $query->update(['value' => $row['value']]);
            }
        });
        LandingPage::clearCache();
        $this->info("Restored storage/app/{$path}.");

        return self::SUCCESS;
    }

    private function short(?string $value): string
    {
        return Str::limit(str_replace(["\r\n", "\n"], ' / ', strip_tags((string) $value)), 150);
    }
}
