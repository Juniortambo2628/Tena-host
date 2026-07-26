<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandingContent extends Model
{
    use HasFactory;

    protected $table = 'landing_content';

    protected $fillable = ['section_id', 'content_key', 'value', 'type'];

    public function section(): BelongsTo
    {
        return $this->belongsTo(LandingSection::class, 'section_id');
    }

    /**
     * Cast value based on type.
     */
    public function getCastValue(): mixed
    {
        return match ($this->type) {
            'json' => json_decode($this->value, true),
            'boolean' => in_array($this->value, [true, 1, '1', 'true'], true),
            'integer' => (int) $this->value,
            'html' => $this->value,
            default => $this->value,
        };
    }
}
