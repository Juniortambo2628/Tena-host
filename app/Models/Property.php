<?php

namespace App\Models;

use App\Traits\BelongsToHost;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Property extends Model
{
    use BelongsToHost, HasFactory, SoftDeletes;

    protected $hostScopeColumn = 'user_id';

    protected $fillable = [
        'user_id',
        'name',
        'address',
        'description',
        'wifi_ssid',
        'occupancy_threshold',
        'logo_path',
        'splash_image_path',
        'branding_json',
        'pms_integration_type',
        'pms_connection_status',
        'pms_last_sync_at',
    ];

    protected $casts = [
        'branding_json' => 'array',
        'pms_last_sync_at' => 'datetime',
    ];

    protected $with = [
        'host:id,first_name,last_name,email,phone_number',
    ];

    /**
     * Get the host that owns the property.
     */
    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the access points for the property.
     */
    public function accessPoints(): HasMany
    {
        return $this->hasMany(AccessPoint::class);
    }

    /**
     * Get the guests for the property.
     */
    public function guests(): HasMany
    {
        return $this->hasMany(Guest::class);
    }

    /**
     * Get the amenities for the property.
     */
    public function amenities(): HasMany
    {
        return $this->hasMany(Amenity::class);
    }
}
