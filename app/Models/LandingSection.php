<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LandingSection extends Model
{
    use HasFactory;

    protected $fillable = ['page_id', 'section_key', 'title', 'subtitle', 'badge', 'bg', 'is_active', 'sort_order'];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(LandingPage::class, 'page_id');
    }

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
     * Public page caches are per page; any CMS write clears them all.
     */
    public static function clearCache(): void
    {
        LandingPage::clearCache();
    }
}
