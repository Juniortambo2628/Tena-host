<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketingEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'guest_id',
        'event_type',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function guest()
    {
        return $this->belongsTo(Guest::class);
    }
}
