<?php

namespace App\Models;

use App\Traits\BelongsToHost;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    use BelongsToHost, HasFactory;

    protected $fillable = [
        'guest_id',
        'property_id',
        'amenity_id',
        'status',
        'total',
    ];

    /**
     * Get the guest who placed the order.
     */
    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    /**
     * Get the property where the order was placed.
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Get the amenity that was ordered.
     */
    public function amenity(): BelongsTo
    {
        return $this->belongsTo(Amenity::class);
    }
}
