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
        'payment_status',
        'payer_phone',
        'mpesa_checkout_request_id',
        'mpesa_receipt',
        'paid_at',
        'platform_fee',
        'settled_at',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'paid_at' => 'datetime',
        'settled_at' => 'datetime',
    ];

    /** What the host is owed for this order once it's paid. */
    public function getHostPayoutAttribute(): float
    {
        return round((float) $this->total - (float) $this->platform_fee, 2);
    }

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
