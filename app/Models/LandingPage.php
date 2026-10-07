<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

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

    public static function cacheKey(string $slug): string
    {
        return "landing_page:{$slug}";
    }

    public static function clearCache(): void
    {
        static::pluck('slug')->each(fn ($slug) => Cache::forget(static::cacheKey($slug)));
    }
}
