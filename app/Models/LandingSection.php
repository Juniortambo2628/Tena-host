<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\Cache;

class LandingSection extends Model
{
    use HasFactory;

    protected $fillable = ['section_key', 'title', 'subtitle', 'badge', 'bg', 'is_active', 'sort_order'];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function contents(): HasMany
    {
        return $this->hasMany(LandingContent::class, 'section_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(LandingMedia::class, 'section_id');
    }

    /**
     * Get all content as key => value array.
     */
    public function getContentMap(): array
    {
        return $this->contents->pluck('value', 'content_key')->toArray();
    }

    /**
     * Get media as key => path array.
     */
    public function getMediaMap(): array
    {
        return $this->media->pluck('original_path', 'media_key')->toArray();
    }

    /**
     * Get full section data for public rendering.
     */
    public function toPublicArray(): array
    {
        $content = $this->getContentMap();
        $media = $this->getMediaMap();

        return [
            'id' => $this->id,
            'section_key' => $this->section_key,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'badge' => $this->badge,
            'bg' => $this->bg,
            'content' => $content,
            'media' => $media,
        ];
    }

    /**
     * Get all active sections ordered, cached for 1 hour.
     */
    public static function getActiveSections(): array
    {
        return Cache::remember('landing_sections', 3600, function () {
            return static::where('is_active', true)
                ->orderBy('sort_order')
                ->with(['contents', 'media'])
                ->get()
                ->map->toPublicArray()
                ->toArray();
        });
    }

    public static function clearCache(): void
    {
        Cache::forget('landing_sections');
    }
}
