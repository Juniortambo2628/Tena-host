<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MpesaTransaction extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'MerchantRequestID',
        'CheckoutRequestID',
        'Amount',
        'MpesaReceiptNumber',
        'PhoneNumber',
        'Status',
        'ResultDesc',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getMerchantRequestIdAttribute(): ?string
    {
        return $this->attributes['MerchantRequestID'] ?? null;
    }

    public function getCheckoutRequestIdAttribute(): ?string
    {
        return $this->attributes['CheckoutRequestID'] ?? null;
    }

    public function getAmountAttribute(): float
    {
        return (float) ($this->attributes['Amount'] ?? 0);
    }

    public function getMpesaReceiptNumberAttribute(): ?string
    {
        return $this->attributes['MpesaReceiptNumber'] ?? null;
    }

    public function getPhoneNumberAttribute(): ?string
    {
        return $this->attributes['PhoneNumber'] ?? null;
    }

    public function getStatusAttribute(): ?string
    {
        return $this->attributes['Status'] ?? null;
    }

    public function getResultDescAttribute(): ?string
    {
        return $this->attributes['ResultDesc'] ?? null;
    }
}
