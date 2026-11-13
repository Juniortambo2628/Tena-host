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
 * Brings the live site's pricing to the offer (Nov 2026): get your TenaFi
 * device, pay once; your first 3 months of Basic are free; then choose Free,
 * Basic, Starter or Growth. Pay monthly is gone.
 *
 * It never overwrites what Glen wrote and never deletes stored content:
 *  - new fields (free_months, the three steps, the card wording, ...) are
 *    filled only where they are missing or empty;
 *  - the Free plan and the new FAQs are added where they aren't there yet;
 *  - a field is updated only while it still holds exactly the wording TenaFi
 *    shipped before (OLD_DEFAULTS), so an admin's own wording is left alone;
 *  - a plan's features saved as HTML or with tabs/runs of spaces between the
 *    items (the run-on Growth list) become one item per line;
 *  - "Free" is added to the sign-up forms' plan question.
 * Everything else that still mentions Pay monthly, upfront, a trial or
 * installation next to a price is listed in the admin checklist.
 *
 *   --dry-run   lists every change and the checklist, and stops.
 *   (no flag)   saves a backup, then applies.
 *   --restore=  puts a backup back.
 */
class PricingOfferCommand extends Command
{
    protected $signature = 'tenafi:pricing-offer {--dry-run : Show the changes without saving anything} {--restore= : Restore a backup file from storage/app/cms-backups}';

    protected $description = 'Bring the live pricing to "device once, 3 months of Basic free, then choose" (fills only new or empty fields; --dry-run first)';

    /** Live pricing section's page => the blueprint page its defaults come from. */
    private const PRICING = ['hosts' => 'hosts', 'v2-hosts' => 'hosts', 'business' => 'business', 'v2-business' => 'business', 'v2' => 'hosts'];

    /** Pages whose FAQ gets the offer's questions (the homepage FAQ stays as it is). */
    private const FAQ = ['hosts' => 'hosts', 'v2-hosts' => 'hosts', 'business' => 'business', 'v2-business' => 'business'];

    private const HOME = ['home', 'v2'];

    /** New fields on a pricing section (blueprint $offer), filled where empty. */
    private const NEW_FIELDS = [
        'free_months', 'base_free_plan', 'show_free_plan', 'price_unit', 'devices_picker_label',
        'intro_line', 'then_line', 'device_line_card', 'free_plan_line',
        'intro_free_phrase', 'intro_paid_phrase', 'example_full_text', 'offer_note',
    ];

    private const NEW_FAQS = [
        'What’s free?', 'What happens after 3 months?', 'Do I keep the device?',
        'What’s the difference between Free and Basic?', 'Can I start on Starter or Growth?', 'How do I pay for the device?',
    ];

    /**
     * Wording TenaFi shipped before (database/blueprints/public_pages.php up to
     * #99): a live field still holding exactly one of these gets the new
     * default. [blueprint page, section, key] => old values.
     */
    private const OLD_DEFAULTS = [
        ['hosts', 'pricing', 'subtitle', ['The device, installation, WiFi page, guest list and support. Month to month.']],
        ['business', 'pricing', 'subtitle', ['The device, installation, WiFi page, customer list and support. Month to month.']],
        ['hosts', 'pricing', 'footnote', ["Month to month; cancel with 30 days\u{2019} notice. Pay yearly and get 2 months free. Multi-unit operators: 20% off from 10 units, 30% off from 50. Your TenaFi device is bought upfront or over your first 12 months, and then it\u{2019}s yours. WhatsApp and SMS allowances included; heavy use billed at cost."]],
        ['business', 'pricing', 'footnote', ["Month to month; cancel with 30 days\u{2019} notice. Pay yearly and get 2 months free. Several locations? We\u{2019}ll quote group pricing. Your TenaFi device is bought upfront or over your first 12 months, and then it\u{2019}s yours. WhatsApp and SMS allowances included; heavy use billed at cost."]],
        ['hosts', 'pricing', 'example_text', ['{label} pays {buy} in year one buying the device, or {monthly} paying monthly. Buying saves {saving}.']],
        ['business', 'pricing', 'example_text', ['{label} pays {buy} in year one buying the device, or {monthly} paying monthly. Buying saves {saving}.']],
        ['hosts', 'pricing', 'example_label', ['A one-bedroom rental on Starter with TenaFi Lite']],
        ['hosts', 'pricing', 'devices_note', ['Coverage is for open space. Walls and floors reduce it, so we check your space at installation.']],
        ['business', 'pricing', 'devices_note', ['Coverage is for open space. Walls and floors reduce it, so we check your space at installation.']],
        ['home', 'hero', 'device_line', ['Plus a TenaFi device from {device_price}, or KES 0 upfront on monthly.']],
        ['home', 'cta_banner__founding', 'device_line', ['Plus a TenaFi device from {device_price}, or KES 0 upfront on monthly.']],
        ['home', 'cta_banner__founding', 'pricing_note', ['A month, per unit for rentals. Pay yearly and get 2 months free. Events are quoted per event.']],
        ['pricing', 'seo', 'meta_title', ['TenaFi devices and pricing: buy once or pay monthly']],
        ['pricing', 'seo', 'meta_description', ['TenaFi Lite, Pro and Outdoor: one device for every size of space, installed and managed for you. Buy upfront, or pay monthly with nothing upfront.']],
        ['pricing', 'devices', 'subtitle', ['Every TenaFi device runs your branded WiFi page, captures your guest list and is installed, set up and managed by our team. Pick the one that fits your space.']],
        ['pricing', 'devices', 'upfront_label', ['Buy upfront, installed']],
        ['pricing', 'devices', 'perks.0.title', ['Installed for you']],
        ['pricing', 'pay_options', 'badge', ['Two ways to pay']],
        ['pricing', 'pay_options', 'title', ['Buy your device once, or spread it out.']],
        ['pricing', 'pay_options', 'subtitle', ['Same device, same plans, same service. Buying upfront is the best value; paying monthly spreads the device over your first 12 months.']],
        ['pricing', 'pay_options', 'notes.0.text', ['On a call, a video call or a visit, we show you TenaFi and survey your space. Your device payment or first monthly charge starts after installation.']],
        ['pricing', 'pay_options', 'notes.2.title', ['Leaving early on monthly']],
        ['pricing', 'pay_options', 'notes.2.text', ['Pay off the rest of the device or return it. Upfront: 30 days’ notice.']],
        ['pricing', 'pay_options', 'footnote', ['Prices in KES. Paying yearly on the upfront option: 2 months free.']],
        ['pricing', 'pay_options', 'cta_text', ['Book a demo and our team will survey your space and recommend the right devices.']],
    ];

    /** Plan features and buttons TenaFi shipped before, by audience and plan id. */
    private const OLD_PLANS = [
        'hosts' => [
            'basic' => ['features' => "Branded WiFi page and guest list\nA review request to every guest, by SMS and email\nA campaign every quarter", 'cta' => 'Choose Basic'],
            'starter' => ['features' => "Everything in Basic\nA campaign and results report every month [[monthly_report]]\nUpsells, and your Tena Direct page at 0% commission [[tena_direct]]"],
            'growth' => ['features' => "Everything in Starter\n2 to 4 campaigns a month\nFeatured on Tena Direct, plus VIP WiFi tiers [[vip_wifi]]"],
        ],
        'business' => [
            'basic' => ['features' => "A Google review request to every customer\nBy SMS and email\nA campaign every quarter", 'cta' => 'Choose Basic'],
            'starter' => ['features' => "Everything in Basic\nA campaign every month on WhatsApp, SMS and email\nA monthly report on reviews and return visits [[monthly_report]]"],
            'growth' => ['features' => "Everything in Starter\n2 to 4 campaigns a month\nFree and VIP WiFi tiers [[vip_wifi]]"],
        ],
    ];

    /** The Pay monthly FAQs TenaFi shipped (#90): their old answer => the new one. */
    private const OLD_FAQ_ANSWERS = [
        'Do I have to buy the device?' => [
            'old' => 'No. Choose Pay monthly and pay KES 0 upfront. Buying the device upfront costs less over your first year.',
            'new' => 'Yes. You buy your TenaFi device once and it’s yours, and your first 3 months of Basic are free.',
        ],
        'What if I leave?' => [
            'old' => 'It’s month to month with 30 days’ notice. On Pay monthly, if you leave in the first 12 months you pay off the rest of the device or return it.',
            'new' => 'After your free months it’s month to month with 30 days’ notice. The device stays yours.',
        ],
    ];

    /** Words that shouldn't show on the live site any more (checklist). */
    private const STALE = '/pay monthly|paying monthly|\bupfront\b|\+\s*KES\s*[\d,]+\s*\/\s*mo\b|\/mo\b|free trial|one-month trial|trial for december|nothing upfront|for 12 months, then/i';

    /** "Installed" and "installation" next to prices (checklist), on these sections' price wording. */
    private const INSTALL = '/\binstall(ed|ation)\b/i';

    /** @var list<array{section_id: int, key: string, where: string, old: ?string, new: string}> */
    private array $changes = [];

    /** @var list<array{page: string, key: string}> */
    private array $newSections = [];

    /** @var list<string> */
    private array $checklist = [];

    public function handle(): int
    {
        if ($file = $this->option('restore')) {
            return $this->restore($file);
        }

        $this->changes = $this->checklist = $this->newSections = [];
        $this->plan();

        foreach ($this->newSections as $s) {
            $this->line("<comment>[{$s['page']}]</comment> new section: {$s['key']} (the offer's FAQs)");
        }
        foreach ($this->changes as $c) {
            $this->line("<comment>[{$c['where']}]</comment> {$c['key']}");
            $this->line('  - '.($c['old'] === null ? '(new field)' : ($c['old'] === '' ? '(empty)' : $this->short($c['old']))));
            $this->line('  + '.$this->short($c['new']));
        }
        $this->newLine();
        $this->line('<options=bold>Admin checklist: check or edit these by hand in Admin -> Public Pages</>');
        foreach (array_unique($this->checklist) as $item) {
            $this->line("  [ ] {$item}");
        }
        $this->newLine();

        if (! $this->changes && ! $this->newSections) {
            $this->info('Nothing to fill: the live pricing already has the offer.');

            return self::SUCCESS;
        }
        $this->info(count($this->changes).' field(s) to fill or update'.($this->newSections ? ', '.count($this->newSections).' section(s) to add' : '').'.');

        if ($this->option('dry-run')) {
            $this->warn('Dry run: nothing was saved. Run without --dry-run to back up and apply.');

            return self::SUCCESS;
        }

        // The backup first; the ids of sections it adds are written into it afterwards.
        $backup = $this->backup([]);
        $created = [];
        DB::transaction(function () use (&$created) {
            foreach ($this->newSections as $s) {
                PageBlueprint::addSections($s['page'], [$s['key'] => null]);
                $created[] = LandingSection::where('section_key', $s['key'])->whereHas('page', fn ($q) => $q->where('slug', $s['page']))->value('id');
            }
            foreach ($this->changes as $c) {
                LandingContent::updateOrCreate(['section_id' => $c['section_id'], 'content_key' => $c['key']], [
                    'value' => $c['new'],
                    'type' => str_starts_with($c['new'], '[') ? 'json' : (str_contains($c['new'], '<') ? 'html' : 'text'),
                ]);
            }
        });
        if ($created = array_values(array_filter($created))) {
            $this->backup($created, $backup);
        }
        LandingPage::clearCache();
        $this->info("Backup saved: storage/app/{$backup}");
        $this->info('Applied. To undo: php artisan tenafi:pricing-offer --restore='.basename($backup));

        return self::SUCCESS;
    }

    private function plan(): void
    {
        $blueprint = PageBlueprint::definitions();

        foreach (self::PRICING as $slug => $bp) {
            if (! $pricing = $this->section($slug, 'pricing')) {
                continue;
            }
            $defaults = PageBlueprint::flatten($blueprint[$bp]['pricing']['content'] ?? []);
            $live = $pricing->contents->pluck('value', 'content_key')->all();
            $where = "{$slug}/pricing";

            foreach (self::NEW_FIELDS as $key) {
                $this->fillEmpty($pricing, $where, $live, $key, $defaults[$key] ?? '');
            }
            // The three steps, where the section has none.
            if (trim(strip_tags((string) ($live['steps.0.title'] ?? ''))) === '') {
                foreach ($defaults as $key => $value) {
                    if (str_starts_with($key, 'steps.')) {
                        $this->change($pricing, $where, $key, $live[$key] ?? null, $value);
                    }
                }
            }
            $this->oldDefaults($pricing, $where, $live, $bp, 'pricing', $defaults);
            // The hosts example moves to Basic with its label (only while the label is the old one).
            if ($bp === 'hosts' && ($live['example_label'] ?? null) === 'A one-bedroom rental on Starter with TenaFi Lite' && ($live['example_plan'] ?? null) === 'starter') {
                $this->change($pricing, $where, 'example_plan', 'starter', $defaults['example_plan']);
            }
            $this->plans($pricing, $where, $live, $bp, $blueprint[$bp]['pricing']['content']['plans'] ?? []);
            $this->scan($pricing, $slug, $live, true);
        }

        foreach (self::FAQ as $slug => $bp) {
            if ($faq = $this->section($slug, 'faq')) {
                $this->faqs($faq, "{$slug}/faq", $blueprint[$bp]['faq']['content']['items'] ?? []);
            }
        }

        foreach (self::HOME as $slug) {
            foreach (['hero', 'cta_banner__founding'] as $key) {
                if (! $section = $this->section($slug, $key)) {
                    continue;
                }
                $defaults = PageBlueprint::flatten($blueprint['home'][$key]['content'] ?? []);
                $live = $section->contents->pluck('value', 'content_key')->all();
                $this->fillEmpty($section, "{$slug}/{$key}", $live, 'free_price_line', $defaults['free_price_line'] ?? '');
                $this->oldDefaults($section, "{$slug}/{$key}", $live, 'home', $key, $defaults);
                $this->scan($section, $slug, $live);
            }
        }

        // /pricing: its devices and "Good to know" wording, and its own FAQ.
        foreach (['seo', 'devices', 'pay_options'] as $key) {
            if ($section = $this->section('pricing', $key)) {
                $defaults = PageBlueprint::flatten($blueprint['pricing'][$key]['content'] ?? []);
                $live = $section->contents->pluck('value', 'content_key')->all();
                $this->oldDefaults($section, "pricing/{$key}", $live, 'pricing', $key, $defaults);
                $this->scan($section, 'pricing', $live, $key !== 'seo');
            }
        }
        if (LandingPage::where('slug', 'pricing')->exists() && ! $this->section('pricing', 'faq')) {
            $this->newSections[] = ['page' => 'pricing', 'key' => 'faq'];
        }

        // Sign-up forms: "Free" on the plan question.
        foreach (['hosts', 'business', 'v2-hosts', 'v2-business'] as $slug) {
            if ($signup = $this->section($slug, 'signup')) {
                $this->signupPlans($signup, "{$slug}/signup");
            }
        }

        // The rest of the live words that still say Pay monthly, a trial or "installed" by a price.
        foreach (['home', 'v2', 'events', 'v2-events'] as $slug) {
            LandingSection::with('contents')->whereHas('page', fn ($q) => $q->where('slug', $slug))
                ->whereNotIn('section_key', ['hero', 'cta_banner__founding', 'trial_slots'])->get()
                ->each(fn (LandingSection $s) => $this->scan($s, $slug, $s->contents->pluck('value', 'content_key')->all()));
        }

        $this->checklist[] = 'TrialSlots counter (show_slots, Site-wide -> Slot counter): keep it, relabel it, or turn it off? It is left as it is. [open decision]';
        $this->checklist[] = 'VAT: are prices incl. or excl. 16% VAT? Fill vat_note on each pricing section (empty = not shown). [open decision]';
        $this->checklist[] = 'Device payment terms: all at booking, all at installation, or half and half? Then complete the FAQ “How do I pay for the device?”. [open decision]';
        $this->checklist[] = 'Multi-unit discount: does it start at 5 or 10 units? /hosts FAQ “I manage lots of units.” says 10. [open decision]';
    }

    /** A new field, filled only where it is missing or empty. */
    private function fillEmpty(LandingSection $section, string $where, array $live, string $key, string $default): void
    {
        if ($default !== '' && trim(strip_tags((string) ($live[$key] ?? ''))) === '') {
            $this->change($section, $where, $key, $live[$key] ?? null, $default);
        }
    }

    /** Fields still holding exactly the wording TenaFi shipped before get the new default. */
    private function oldDefaults(LandingSection $section, string $where, array $live, string $bp, string $sectionKey, array $defaults): void
    {
        foreach (self::OLD_DEFAULTS as [$page, $key, $field, $olds]) {
            if ($page !== $bp || $key !== $sectionKey || ! array_key_exists($field, $live) || ! isset($defaults[$field])) {
                continue;
            }
            if (in_array((string) $live[$field], $olds, true) && $live[$field] !== $defaults[$field]) {
                $this->change($section, $where, $field, $live[$field], $defaults[$field]);
            }
        }
    }

    /** The Free plan, plans still on the old wording, run-on feature lists, and suggestions. */
    private function plans(LandingSection $section, string $where, array $live, string $bp, array $defaultPlans): void
    {
        $byId = [];
        for ($i = 0; array_key_exists("plans.{$i}.label", $live) || array_key_exists("plans.{$i}.id", $live); $i++) {
            $byId[strtolower(trim(strip_tags((string) ($live["plans.{$i}.id"] ?? ''))))] = $i;
        }
        $defaults = collect($defaultPlans)->keyBy('id');

        // Free, at the next index (the cards sort by price, so it shows first).
        if (! isset($byId['free']) && ($free = $defaults->get('free'))) {
            $next = count($byId);
            foreach ($free as $field => $value) {
                if ($value !== '') {
                    $this->change($section, $where, "plans.{$next}.{$field}", null, (string) $value);
                }
            }
        }

        foreach ($byId as $id => $i) {
            $default = $defaults->get($id);
            $old = self::OLD_PLANS[$bp][$id] ?? [];
            $features = (string) ($live["plans.{$i}.features"] ?? '');

            if ($default && isset($old['features']) && $features === $old['features']) {
                $this->change($section, $where, "plans.{$i}.features", $features, $default['features']);
            } elseif ($fixed = $this->oneItemPerLine($features)) {
                $this->change($section, $where, "plans.{$i}.features", $features, $fixed);
            } elseif ($default && trim($features) !== '' && count(static::lines($features)) === 1 && count(static::lines($default['features'])) > 1) {
                $this->checklist[] = "{$where} plan “{$id}” features are on one line: put one item per line. Stored as: ".json_encode(mb_substr($features, 0, 160), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            } elseif ($default && $features !== $default['features']) {
                $this->checklist[] = "{$where} plan “{$id}” features: suggested for the offer: ".str_replace("\n", ' / ', $default['features']);
            }

            if ($default && isset($old['cta']) && ($live["plans.{$i}.cta"] ?? null) === $old['cta']) {
                $this->change($section, $where, "plans.{$i}.cta", $old['cta'], $default['cta']);
            }
        }
    }

    /** A list saved as HTML, or with tabs or runs of spaces between items, as one item per line; null if fine. */
    private function oneItemPerLine(string $value): ?string
    {
        if (trim($value) === '') {
            return null;
        }
        if (preg_match('/<br|<\/p>|<\/div>|<\/li>|&lt;br|\x{2028}|\x{2029}|\x{0085}/iu', $value)) {
            return implode("\n", static::lines($value));
        }
        $lines = static::lines($value);
        if (count($lines) === 1 && preg_match('/\t|[ \x{00A0}]{2,}/u', $value)) {
            $split = array_values(array_filter(array_map('trim', preg_split('/\t+|[ \x{00A0}]{2,}/u', $lines[0]))));

            return count($split) > 1 ? implode("\n", $split) : null;
        }

        return null;
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

    /** The offer's FAQs where their question isn't there yet; the old Pay monthly answers updated. */
    private function faqs(LandingSection $faq, string $where, array $defaults): void
    {
        $live = $faq->contents->pluck('value', 'content_key');
        $same = fn ($q) => mb_strtolower(str_replace(['’', '‘'], "'", trim(html_entity_decode(strip_tags((string) $q), ENT_QUOTES | ENT_HTML5, 'UTF-8'))));
        $questions = $live->filter(fn ($v, $k) => preg_match('/^items\.\d+\.question$/', $k))->map($same);
        $next = $questions->keys()->map(fn ($k) => (int) explode('.', $k)[1])->max();
        $next = $next === null ? 0 : $next + 1;

        foreach (self::NEW_FAQS as $question) {
            $item = collect($defaults)->first(fn ($i) => $same($i['question']) === $same($question));
            if (! $item || $questions->contains($same($question))) {
                continue;
            }
            $this->change($faq, $where, "items.{$next}.question", null, $item['question']);
            $this->change($faq, $where, "items.{$next}.answer", null, $item['answer']);
            $next++;
        }

        foreach ($questions as $key => $question) {
            $answerKey = str_replace('.question', '.answer', $key);
            foreach (self::OLD_FAQ_ANSWERS as $q => $answers) {
                if ($question === $same($q) && ($live[$answerKey] ?? null) === $answers['old']) {
                    $this->change($faq, $where, $answerKey, $answers['old'], $answers['new']);
                }
            }
        }

        $this->scan($faq, explode('/', $where)[0], $live->all());
    }

    /** "Free" on a sign-up form's plan question (a JSON list of fields), first. */
    private function signupPlans(LandingSection $signup, string $where): void
    {
        foreach ($signup->contents as $content) {
            if (! preg_match('/^steps\.\d+\.fields$/', $content->content_key)) {
                continue;
            }
            $fields = json_decode((string) $content->value, true);
            if (! is_array($fields)) {
                continue;
            }
            $changed = false;
            foreach ($fields as &$field) {
                if (($field['key'] ?? null) === 'plan' && is_array($field['options'] ?? null)
                    && ! collect($field['options'])->contains(fn ($o) => str_starts_with((string) $o, 'free|'))) {
                    array_unshift($field['options'], 'free|Free');
                    $changed = true;
                }
            }
            unset($field);
            if ($changed) {
                $this->change($signup, $where, $content->content_key, $content->value, json_encode($fields, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }
            if (str_contains((string) $content->value, '"devicePayment"')) {
                $this->checklist[] = "{$where}: the “How would you like to pay for it?” question is no longer shown (the device is bought once); delete it from the form when convenient.";
            }
        }
    }

    /** Live words that still say Pay monthly, a trial, or "installed" by a price (checklist). */
    private function scan(LandingSection $section, string $slug, array $live, bool $prices = false): void
    {
        $changing = collect($this->changes)->where('section_id', $section->id)->pluck('key')->all();
        foreach ($live as $key => $value) {
            if (in_array($key, $changing, true) || $this->retired($key) || preg_match('/^steps\.\d+\.fields$|^install_|^included$/', $key)) {
                continue;
            }
            $text = trim(preg_replace('/\s+/', ' ', strip_tags((string) $value)));
            $priceWords = $prices && preg_match('/^(subtitle|footnote|upfront_label|note|pricing_note|price_line|.*\.(price|unit|text|steps))$/', $key);
            if ($text !== '' && (preg_match(self::STALE, $text, $m, PREG_OFFSET_CAPTURE) || ($priceWords && preg_match(self::INSTALL, $text, $m, PREG_OFFSET_CAPTURE)))) {
                // The words around what matched, so the part to edit shows even in a long paragraph.
                $from = max(0, $m[0][1] - 60);
                $around = ($from > 0 ? '…' : '').mb_strcut($text, $from, 180).(strlen($text) > $from + 180 ? '…' : '');
                $hint = preg_match('/free trial|one-month trial|trial for december/i', $text)
                    ? ' Suggested: “Get your TenaFi device and your first 3 months of Basic are free. Installations from Monday 16 November, in time for the December high season.”'
                    : '';
                $this->checklist[] = "{$slug}/{$section->section_key} {$key}: “{$m[0][0]}” in: {$around}{$hint}";
            }
        }
    }

    /** Fields the site no longer shows (hidden in the admin too, SectionEditor isRetiredField). */
    private function retired(string $key): bool
    {
        return (bool) (preg_match('/^(pay_(label|buy_\w+|monthly_\w+)|monthly_price_line|device_price_line|monthly_line|example_upfront|example_monthly|example_saving|table_label|row_upfront\w*|row_monthly\w*)$/', $key)
            || preg_match('/^[^.]+\.\d+\.(monthly_kes|months|monthly_addon_kes|addon_months)$/', $key)
            || preg_match('/^options\.\d+\./', $key));
    }

    private function section(string $slug, string $key): ?LandingSection
    {
        return LandingSection::with('contents')->where('section_key', $key)->whereHas('page', fn ($q) => $q->where('slug', $slug))->first();
    }

    private function change(LandingSection $section, string $where, string $key, ?string $old, string $new): void
    {
        $this->changes[] = ['section_id' => $section->id, 'key' => $key, 'where' => $where, 'old' => $old, 'new' => $new];
    }

    /** @param list<int> $createdSections */
    private function backup(array $createdSections, ?string $path = null): string
    {
        $path ??= 'cms-backups/pricing-offer-'.now()->format('Ymd-His').'.json';
        Storage::disk('local')->put($path, json_encode([
            'contents' => collect($this->changes)->map(fn ($c) => ['section_id' => $c['section_id'], 'key' => $c['key'], 'value' => $c['old']])->values(),
            'created_sections' => $createdSections,
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
            // Sections this command added (the /pricing FAQ) are removed again.
            foreach ($data['created_sections'] ?? [] as $id) {
                LandingContent::where('section_id', $id)->delete();
                LandingSection::whereKey($id)->delete();
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
