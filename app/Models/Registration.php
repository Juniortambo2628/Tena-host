<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Registration extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'first_name',
        'last_name',
        'property_type',
        'property_count',
        'units',
        'primary_platform',
        'biggest_challenge',
        'location',
        'phone',
        'message',
        'referral_source',
        'status',
        'agree_updates',
    ];

    protected $casts = [
        'property_count' => 'integer',
        'agree_updates' => 'boolean',
    ];
}
