<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

/**
 * One line in the WiFi log (Admin -> WiFi Portal -> Logs). Writing a line
 * never breaks what was being logged: a failure only goes to the app log.
 */
class WifiEvent extends Model
{
    use MassPrunable;

    public const UPDATED_AT = null;

    /** What each type means, for the log's filter. */
    public const TYPES = [
        'connection_test' => 'Connection test',
        'guest_authorized' => 'Guest connected',
        'guest_refused' => 'Guest not connected',
        'controller_login_failed' => 'Controller login failed',
        'device_sync_failed' => 'Access point check failed',
        'ap_offline' => 'Access point offline',
        'ap_online' => 'Access point back online',
        'settings_saved' => 'Settings saved',
    ];

    protected $fillable = ['level', 'type', 'message', 'context', 'property_id'];

    protected $casts = ['context' => 'array', 'created_at' => 'datetime'];

    public static function record(string $type, string $message, array $context = [], string $level = 'info', ?int $propertyId = null): void
    {
        try {
            static::create(['type' => $type, 'message' => mb_substr($message, 0, 500), 'context' => $context ?: null, 'level' => $level, 'property_id' => $propertyId]);
        } catch (\Throwable $e) {
            Log::warning('WiFi log line not written: '.$e->getMessage(), ['type' => $type]);
        }
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /** Kept for 30 days (model:prune, daily). */
    public function prunable(): Builder
    {
        return static::where('created_at', '<', now()->subDays(30));
    }
}
