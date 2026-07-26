<?php

namespace App\Models;

use App\Traits\BelongsToHost;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Guest extends Model
{
    use BelongsToHost, HasFactory, SoftDeletes;

    protected $fillable = [
        'property_id',
        'user_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'last_connected',
        'total_visits',
        'source',
        'external_id',
        'check_in',
        'check_out',
    ];

    protected $casts = [
        'last_connected' => 'datetime',
        'check_in' => 'date',
        'check_out' => 'date',
    ];

    /**
     * Get the property the guest is associated with.
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Get the user account linked to this guest.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the orders for the guest.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
