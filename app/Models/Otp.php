<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Otp extends Model
{
    use HasFactory;

    protected $fillable = [
        'identifier',
        'code',
        'purpose',
        'expires_at',
        'used_at',
        'metadata',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function scopeValid($query, string $identifier, string $purpose)
    {
        return $query
            ->where('identifier', $identifier)
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->where('expires_at', '>', now());
    }

    public function markUsed(): void
    {
        $this->update(['used_at' => now()]);
    }
}
