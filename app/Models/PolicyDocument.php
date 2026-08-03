<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PolicyDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'title',
        'description',
        'content',
        'type',
        'is_published',
        'version',
        'effective_date',
        'last_reviewed_at',
        'last_reviewed_by',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'effective_date' => 'datetime',
        'last_reviewed_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
