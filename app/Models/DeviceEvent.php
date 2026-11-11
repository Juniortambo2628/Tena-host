<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** The log of a device agreement: recorded, payments, status changes, notes. */
class DeviceEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['device_agreement_id', 'user_id', 'type', 'note', 'meta'];

    protected $casts = ['meta' => 'array', 'created_at' => 'datetime'];

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(DeviceAgreement::class, 'device_agreement_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
