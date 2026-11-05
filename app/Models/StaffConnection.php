<?php

namespace App\Models;

use App\Traits\BelongsToHost;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A staff member's sign-in on a site's staff network: who (name and staff
 * ID or work email) and which device. Never a guest: kept out of the
 * customer list and campaigns.
 */
class StaffConnection extends Model
{
    use BelongsToHost;

    protected $fillable = ['property_id', 'name', 'identifier', 'device_mac', 'visits', 'first_seen_at', 'last_seen_at'];

    protected $casts = [
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
