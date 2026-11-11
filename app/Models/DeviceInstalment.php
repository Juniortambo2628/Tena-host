<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One monthly payment on a device agreement. */
class DeviceInstalment extends Model
{
    protected $fillable = [
        'device_agreement_id', 'number', 'due_on', 'amount_kes', 'paid_kes', 'paid_at', 'reference', 'status',
    ];

    protected $casts = [
        'due_on' => 'date',
        'paid_at' => 'datetime',
        'amount_kes' => 'integer',
        'paid_kes' => 'integer',
        'number' => 'integer',
    ];

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(DeviceAgreement::class, 'device_agreement_id');
    }

    public function isSettled(): bool
    {
        return in_array($this->status, ['paid', 'waived'], true);
    }

    public function remaining(): int
    {
        return $this->isSettled() ? 0 : max(0, $this->amount_kes - $this->paid_kes);
    }
}
