<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class LandingPage extends Model
{
    /** Non-routable page holding header, footer, plans and feature status. */
    public const SITE = 'site';

    protected $fillable = ['slug', 'name', 'is_routable', 'is_active', 'sort_order'];

    protected $casts = [
        'is_routable' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /** Public URL path: "/" for home, "/{slug}" otherwise. */
    public function path(): string
    {
        return $this->slug === 'home' ? '/' : "/{$this->slug}";
    }

    public function sections(): HasMany
    {
        return $this->hasMany(LandingSection::class, 'page_id')->orderBy('sort_order');
    }

    /**
     * Active sections of a page in public shape, cached per page.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function publicSections(string $slug): array
    {
        return Cache::remember(static::cacheKey($slug), 3600, function () use ($slug) {
            $page = static::where('slug', $slug)->where('is_active', true)->first();

            if (! $page) {
                return [];
            }

            return $page->sections()
                ->where('is_active', true)
                ->with(['contents', 'media'])
                ->get()
                ->map->toPublicArray()
                ->values()
                ->toArray();
        });
    }

    /**
     * Site-wide sections keyed by section_key (header, footer, plans, ...).
     *
     * @return array<string, array<string, mixed>>
     */
    public static function siteSections(): array
    {
        $sections = collect(static::publicSections(self::SITE))->keyBy('section_key')->toArray();

        // A feature whose admin switch is on is live on the site too, so its "Coming soon" badge goes.
        if (isset($sections['feature_status']['content'])) {
            $content = &$sections['feature_status']['content'];
            foreach ($content as $key => $feature) {
                if (preg_match('/^items\.(\d+)\.key$/', $key, $m) && static::featureSwitchedOn(strip_tags((string) $feature))) {
                    $content["items.{$m[1]}.status"] = 'live';
                }
            }
            unset($content);
        }

        return $sections;
    }

    /**
     * Site features that ship behind an admin switch (Admin -> Settings): once
     * the switch is on, the site stops calling them "Coming soon".
     */
    public const FEATURE_SWITCHES = [
        'event_sponsors' => 'event_accounts_enabled',
        'event_question' => 'event_accounts_enabled',
        'event_tagging' => 'event_accounts_enabled',
    ];

    public static function featureSwitchedOn(string $feature): bool
    {
        $switch = static::FEATURE_SWITCHES[$feature] ?? null;

        return $switch !== null && filter_var(Setting::getValue($switch, '0'), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Header megamenus, keyed by page path ("/hosts" => [...items]).
     *
     * Built from the pages themselves: any active section with both an
     * "anchor" and a "menu_label" becomes a menu item, so the menus always
     * match what each page actually shows.
     *
     * @return array<string, array<int, array{label: string, href: string, description: string}>>
     */
    public static function navMenus(): array
    {
        return Cache::remember(static::cacheKey('menus'), 3600, fn () => static::where('is_routable', true)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->mapWithKeys(fn (self $page) => [$page->path() => collect(static::publicSections($page->slug))
                ->filter(fn ($s) => filled($s['content']['menu_label'] ?? null) && filled($s['content']['anchor'] ?? null))
                ->map(fn ($s) => [
                    'label' => strip_tags($s['content']['menu_label']),
                    'href' => $page->path().'#'.$s['content']['anchor'],
                    'description' => Str::limit(strip_tags($s['content']['menu_description'] ?? $s['content']['title'] ?? ''), 80),
                ])
                ->values()
                ->all()])
            ->filter()
            ->all());
    }

    /**
     * The plans on a page's live pricing section (Admin -> Public Pages), so
     * other pages quote the same names and prices instead of copying them.
     *
     * @return list<array{id: string, label: string, price: string, price_kes: int}>
     */
    public static function livePlans(string $slug = 'hosts'): array
    {
        $pricing = collect(static::publicSections($slug))->firstWhere('section_key', 'pricing');
        $content = $pricing['content'] ?? [];

        $plans = [];
        for ($i = 0; isset($content["plans.{$i}.label"]); $i++) {
            $plans[] = [
                'id' => strip_tags((string) ($content["plans.{$i}.id"] ?? '')),
                'label' => strip_tags((string) $content["plans.{$i}.label"]),
                'price' => strip_tags((string) ($content["plans.{$i}.price"] ?? '')),
                'price_kes' => (int) preg_replace('/\D/', '', (string) ($content["plans.{$i}.price_kes"] ?? '')),
            ];
        }

        return $plans;
    }

    /**
     * The TenaFi devices on the /pricing page's "devices" section (Admin ->
     * Public Pages -> Devices & pricing), so the sign-up form quotes the
     * same prices as the page.
     *
     * They are kept once, on that section, and shared with every page's
     * pricing (/hosts, /business) the way the plans are.
     *
     * @return list<array{id: string, name: string, price_kes: int, monthly_kes: int, months: int, image: string, best_for: string, coverage: string, coverage_label: string, capacity: string, capacity_label: string}>
     */
    public static function liveDevices(): array
    {
        $section = collect(static::publicSections('pricing'))->firstWhere('section_key', 'devices');
        $content = $section['content'] ?? [];
        $kes = fn ($value) => (int) preg_replace('/\D/', '', (string) $value);
        $text = fn (string $key) => trim(strip_tags((string) ($content[$key] ?? '')));

        $devices = [];
        for ($i = 0; isset($content["items.{$i}.name"]); $i++) {
            $id = $text("items.{$i}.id");
            $devices[] = [
                'id' => $id,
                'name' => $text("items.{$i}.name"),
                'price_kes' => $kes($content["items.{$i}.price_kes"] ?? ''),
                'monthly_kes' => $kes($content["items.{$i}.monthly_kes"] ?? ''),
                'months' => $kes($content["items.{$i}.months"] ?? '') ?: 12,
                // The uploaded picture, else TenaFi's own drawing of that device.
                'image' => $section['media']["device_{$i}_image"] ?? (in_array($id, ['lite', 'pro', 'outdoor'], true) ? "/images/devices/tenafi-{$id}.svg" : ''),
                'best_for' => $text("items.{$i}.best_for"),
                'coverage' => $text("items.{$i}.coverage"),
                'coverage_label' => $text("items.{$i}.coverage_label"),
                'capacity' => $text("items.{$i}.capacity"),
                'capacity_label' => $text("items.{$i}.capacity_label"),
            ];
        }

        return $devices;
    }

    public static function cacheKey(string $slug): string
    {
        return "landing_page:{$slug}";
    }

    public static function clearCache(): void
    {
        // A plain loop on purpose: Collection::each() stops at the first
        // callback returning false, which Cache::forget() does for any key
        // that isn't cached, leaving later pages stale.
        foreach (static::pluck('slug')->push('menus') as $slug) {
            Cache::forget(static::cacheKey($slug));
        }
    }
}
