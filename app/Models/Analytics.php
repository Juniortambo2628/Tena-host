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

    /** Add to today's count for a metric (e.g. event.12.sponsor_views). */
    public static function bump(string $metric, int $by = 1): void
    {
        $row = static::where('metric_name', $metric)->whereDate('date_recorded', today())->first()
            ?? static::create(['metric_name' => $metric, 'metric_value' => 0, 'date_recorded' => today()]);
        $row->increment('metric_value', $by);
    }

    /** A metric's total over all days. */
    public static function total(string $metric): int
    {
        return (int) static::where('metric_name', $metric)->sum('metric_value');
    }
}
