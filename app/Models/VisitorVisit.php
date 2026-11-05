<?php

namespace App\Models;

use App\Traits\BelongsToHost;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One visit in a site's reception log: who came, who they came to see, when
 * they arrived and (once reception signs them out) when they left. Made by
 * the WiFi sign-in, or by reception for a walk-in.
 */
class VisitorVisit extends Model
{
    use BelongsToHost;

    protected $fillable = ['property_id', 'guest_id', 'name', 'contact', 'visiting', 'arrived_at', 'left_at'];

    protected $casts = [
        'arrived_at' => 'datetime',
        'left_at' => 'datetime',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    /** Log a WiFi sign-in: a guest still signed in today keeps one row (the latest answer wins). */
    public static function recordSignIn(Property $property, Guest $guest, string $visiting): self
    {
        $open = static::where('property_id', $property->id)->where('guest_id', $guest->id)
            ->whereNull('left_at')->where('arrived_at', '>=', today())->latest('arrived_at')->first();

        if ($open) {
            $open->update(['visiting' => $visiting]);

            return $open;
        }

        return static::create([
            'property_id' => $property->id,
            'guest_id' => $guest->id,
            'name' => trim("{$guest->first_name} {$guest->last_name}") ?: 'Visitor',
            'contact' => $guest->phone ?: $guest->email,
            'visiting' => $visiting,
            'arrived_at' => now(),
        ]);
    }
}
