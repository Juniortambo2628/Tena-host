<?php

namespace App\Models;

use App\Traits\BelongsToHost;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessPoint extends Model
{
    use BelongsToHost, HasFactory;

    protected $fillable = [
        'property_id',
        'mac_address',
        'name',
        'status',
        'last_seen',
        'connected_clients_count',
    ];

    protected $casts = [
        'last_seen' => 'datetime',
    ];

    /**
     * Get the property that owns the access point.
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
