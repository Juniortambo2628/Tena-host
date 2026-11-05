<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/** One attendee at one event: the days they connected and their answer to the event's question. */
class EventAttendance extends Pivot
{
    protected $table = 'event_guest';

    public $incrementing = true;

    protected $casts = [
        'days' => 'array',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];
}
