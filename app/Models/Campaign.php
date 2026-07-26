<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'property_id',
        'audience_property_id',
        'name',
        'type',
        'status',
        'subject',
        'content',
        'trigger_event',
        'trigger_delay',
        'target_audience',
        'audience_from',
        'audience_to',
        'schedule_trigger',
        'scheduled_at',
        'total_sent',
        'total_opened',
        'total_clicked',
    ];

    protected $casts = [
        'total_sent' => 'integer',
        'total_opened' => 'integer',
        'total_clicked' => 'integer',
        'audience_from' => 'date',
        'audience_to' => 'date',
        'scheduled_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function events()
    {
        return $this->hasMany(MarketingEvent::class);
    }

    public function getOpenRateAttribute(): float
    {
        return $this->total_sent > 0
            ? round(($this->total_opened / $this->total_sent) * 100, 1)
            : 0;
    }

    public function getClickRateAttribute(): float
    {
        return $this->total_sent > 0
            ? round(($this->total_clicked / $this->total_sent) * 100, 1)
            : 0;
    }
}
