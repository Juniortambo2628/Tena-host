<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * A guest a campaign was sent to (once each), with the token behind their
 * tracked links (/c/{token}/{n}) and email open pixel.
 */
class CampaignRecipient extends Model
{
    protected $fillable = ['campaign_id', 'guest_id', 'token', 'opened_at', 'clicked_at'];

    protected $casts = [
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (CampaignRecipient $recipient) {
            $recipient->token ??= static::newToken();
        });
    }

    public static function newToken(): string
    {
        do {
            $token = Str::random(8);
        } while (static::where('token', $token)->exists());

        return $token;
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }
}
