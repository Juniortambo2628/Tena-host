<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class LandingMedia extends Model
{
    use HasFactory;

    protected $table = 'landing_media';

    protected $fillable = [
        'section_id', 'media_key', 'original_path', 'optimized_path',
        'thumbnail_path', 'mime_type', 'file_size', 'width', 'height',
        'duration', 'sort_order', 'crop_data',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'duration' => 'integer',
        'sort_order' => 'integer',
        'crop_data' => 'array',
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(LandingSection::class, 'section_id');
    }

    /**
     * Get the best available path (optimized > original).
     */
    public function getPathAttribute(): string
    {
        if ($this->optimized_path && Storage::exists($this->optimized_path)) {
            return Storage::url($this->optimized_path);
        }
        return $this->original_path;
    }

    /**
     * Get thumbnail path or fallback to main path.
     */
    public function getThumbnailAttribute(): string
    {
        if ($this->thumbnail_path && Storage::exists($this->thumbnail_path)) {
            return Storage::url($this->thumbnail_path);
        }
        return $this->path;
    }

    /**
     * Check if media is a video.
     */
    public function getIsVideoAttribute(): bool
    {
        return str_starts_with($this->mime_type, 'video/');
    }

    /**
     * Check if media is an image.
     */
    public function getIsImageAttribute(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Get file size in human-readable format.
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 1) . ' ' . $units[$i];
    }
}
