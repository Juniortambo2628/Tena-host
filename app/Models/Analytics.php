<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Analytics extends Model
{
    use HasFactory;

    protected $fillable = [
        'metric_name',
        'metric_value',
        'date_recorded',
    ];

    protected $casts = [
        'metric_value' => 'decimal:2',
        'date_recorded' => 'date',
    ];
}
