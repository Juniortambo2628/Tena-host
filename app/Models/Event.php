<?php

namespace App\Models;

use App\Traits\BelongsToHost;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * A dated event at a venue (a property): a launch, a conference day, a
 * wedding, a match. Attendees are the venue's guests who connected while it
 * ran (event_guest).
 */
class Event extends Model
{
    use BelongsToHost, HasFactory;

    protected $fillable = ['user_id', 'property_id', 'name', 'starts_on', 'ends_on', 'branding_json', 'question', 'sponsor'];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
        'branding_json' => 'array',
        'sponsor' => 'array',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function attendees(): BelongsToMany
    {
        return $this->belongsToMany(Guest::class)
            ->using(EventAttendance::class)
            ->withPivot(['days', 'answer', 'first_seen_at', 'last_seen_at'])
            ->withTimestamps();
    }

    /** upcoming, live (running today) or past. */
    public function status(?Carbon $today = null): string
    {
        $today = ($today ?? now())->copy()->startOfDay();

        return match (true) {
            $this->starts_on->gt($today) => 'upcoming',
            $this->ends_on->lt($today) => 'past',
            default => 'live',
        };
    }
}
