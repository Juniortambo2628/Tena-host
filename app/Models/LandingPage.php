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
        return collect(static::publicSections(self::SITE))->keyBy('section_key')->toArray();
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
