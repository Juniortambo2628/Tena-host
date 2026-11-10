<?php

namespace App\Console\Commands;

use App\Models\LandingContent;
use App\Models\LandingPage;
use App\Models\Setting;
use App\Services\Cms\PageBlueprint;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Puts the devices and pricing (November 2026) live:
 *  - every pricing section's footnote drops "The device stays TenaFi's; we
 *    maintain and replace it" and "Extra devices at the same site: KES 1,500
 *    a month each" (devices are now bought, upfront or over 12 months, and
 *    belong to the client), keeping the rest of the admin's wording;
 *  - the header's "Pricing" link goes to /pricing;
 *  - the /hosts and /business sign-up forms ask which device and how to pay
 *    for it, just before the plan (the admin's other questions stay);
 *  - /pricing is switched on (Admin -> Settings "Pricing page").
 *
 *   --dry-run   lists every change and stops.
 *   (no flag)   saves a backup, then applies.
 *   --restore=  puts a backup back.
 */
class PricingDevicesCommand extends Command
{
    protected $signature = 'tenafi:pricing-devices {--dry-run : Show the changes without saving anything} {--restore= : Restore a backup file from storage/app/cms-backups}';

    protected $description = 'Put the devices and pricing live: footnotes, the header Pricing link and /pricing (with --dry-run and a backup first)';

    /** Pages whose "pricing" section footnote mentions the old device terms. */
    private const PRICING_PAGES = ['hosts', 'business', 'v2', 'v2-hosts', 'v2-business'];

    private const SETTING = 'pricing_page_enabled';

    private const APOSTROPHE = "(?:’|'|&rsquo;|&#8217;|&#x2019;)";

    public function handle(): int
    {
        if ($file = $this->option('restore')) {
            return $this->restore($file);
        }

        $changes = $this->plan();
        $switchOn = ! filter_var(Setting::getValue(self::SETTING, '0'), FILTER_VALIDATE_BOOLEAN);

        if (! $changes && ! $switchOn) {
            $this->info('The devices and pricing are already live. Nothing to do.');

            return self::SUCCESS;
        }

        foreach ($changes as $change) {
            $this->line("<comment>[{$change['where']}]</comment> {$change['key']}");
            if (isset($change['note'])) {
                $this->line('  + '.$change['note']);

                continue;
            }
            [$old, $new] = static::changedPart((string) $change['old'], (string) $change['new']);
            $this->line('  - '.$old);
            $this->line('  + '.$new);
        }
        if ($switchOn) {
            $this->line('<comment>[setting]</comment> /pricing: switch on');
        }
        $this->newLine();
        $this->info(count($changes).' field(s) to change'.($switchOn ? ', and /pricing to switch on.' : '.'));

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
            Setting::setValue(self::SETTING, '1');
        });
        LandingPage::clearCache();
        $this->info('Applied. To undo: php artisan tenafi:pricing-devices --restore='.basename($backup));

        return self::SUCCESS;
    }

    /** @return list<array{id: int, where: string, key: string, old: string, new: string}> */
    private function plan(): array
    {
        $changes = [];

        foreach (self::PRICING_PAGES as $slug) {
            foreach ($this->contents($slug, 'pricing', fn ($q) => $q->where('content_key', 'footnote')) as $row) {
                $new = static::newFootnote((string) $row->value);
                if ($new !== $row->value) {
                    $changes[] = ['id' => $row->id, 'where' => "{$slug}/pricing", 'key' => 'footnote', 'old' => $row->value, 'new' => $new];
                }
            }
        }

        // The header's Pricing link: /#pricing (or #pricing) becomes /pricing.
        foreach ($this->contents(LandingPage::SITE, 'header', fn ($q) => $q->where('content_key', 'like', 'links.%.href')) as $row) {
            if (in_array(trim(strip_tags((string) $row->value)), ['/#pricing', '#pricing'], true)) {
                $changes[] = ['id' => $row->id, 'where' => 'site/header', 'key' => $row->content_key, 'old' => $row->value, 'new' => '/pricing'];
            }
        }

        // The sign-up forms: the device questions go in just before the plan.
        foreach (['hosts', 'business'] as $slug) {
            $wanted = collect(PageBlueprint::definitions()[$slug]['signup']['content']['steps'] ?? [])
                ->flatMap(fn ($step) => $step['fields'] ?? [])
                ->whereIn('key', ['device', 'devicePayment'])
                ->values()->all();
            foreach ($this->contents($slug, 'signup', fn ($q) => $q->where('content_key', 'like', 'steps.%.fields')) as $row) {
                $fields = json_decode((string) $row->value, true) ?: [];
                $keys = array_column($fields, 'key');
                $at = array_search('plan', $keys, true);
                if (! $wanted || $at === false || in_array('device', $keys, true)) {
                    continue;
                }
                array_splice($fields, $at, 0, $wanted);
                $changes[] = [
                    'id' => $row->id, 'where' => "{$slug}/signup", 'key' => $row->content_key, 'old' => $row->value,
                    'new' => json_encode($fields, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'note' => 'new questions before the plan: '.collect($wanted)->pluck('label')->implode(' / '),
                ];
            }
        }

        return $changes;
    }

    /** The footnote without the old device terms, the rest of it untouched. */
    public static function newFootnote(string $text): string
    {
        $a = self::APOSTROPHE;
        $text = preg_replace('/\s*Extra devices at the same site: KES 1,500 a month each\.?/u', '', $text);

        return preg_replace(
            "/The device stays TenaFi{$a}s; we maintain and replace it\\.?/u",
            'Your TenaFi device is bought upfront or over your first 12 months, and then it’s yours.',
            $text,
        );
    }

    private function contents(string $slug, string $sectionKey, callable $scope)
    {
        return LandingContent::query()
            ->whereHas('section', fn ($q) => $q->where('section_key', $sectionKey)->whereHas('page', fn ($p) => $p->where('slug', $slug)))
            ->where($scope)
            ->get();
    }

    private function backup(array $changes): string
    {
        $path = 'cms-backups/pricing-devices-'.now()->format('Ymd-His').'.json';
        Storage::disk('local')->put($path, json_encode([
            'contents' => collect($changes)->map(fn ($c) => ['id' => $c['id'], 'value' => $c['old']])->values(),
            'settings' => [self::SETTING => Setting::getValue(self::SETTING, '0')],
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
            foreach ($data['settings'] ?? [] as $key => $value) {
                Setting::setValue($key, $value);
            }
        });
        LandingPage::clearCache();
        $this->info("Restored storage/app/{$path}.");

        return self::SUCCESS;
    }

    /** Just the part of a long text that changes, with a little context ("…" where cut). */
    public static function changedPart(string $old, string $new, int $context = 30): array
    {
        $start = 0;
        $max = min(strlen($old), strlen($new));
        while ($start < $max && $old[$start] === $new[$start]) {
            $start++;
        }
        $end = 0;
        while ($end < $max - $start && $old[strlen($old) - 1 - $end] === $new[strlen($new) - 1 - $end]) {
            $end++;
        }
        $from = max(0, $start - $context);
        $cut = fn (string $s) => ($from > 0 ? '…' : '')
            .mb_strcut($s, $from, strlen($s) - $from - max(0, $end - $context))
            .($end > $context ? '…' : '');

        return [Str::of($cut($old))->replace("\n", ' / ')->toString(), Str::of($cut($new))->replace("\n", ' / ')->toString()];
    }
}
