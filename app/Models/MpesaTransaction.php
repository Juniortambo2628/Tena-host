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
}
