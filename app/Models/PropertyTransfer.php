<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A property handed from one TenaFi account to another
 * (docs/plans/property-transfer.md). See PropertyTransferService.
 */
class PropertyTransfer extends Model
{
    /** How long the recipient has to accept. */
    public const VALID_DAYS = 7;

    /** After the recipient accepts, the hand-over waits this long so the old owner can still cancel. */
    public const COOLING_OFF_HOURS = 48;

    /** After a transfer, the new owner waits this long before messaging the guests who came with it. */
    public const CAMPAIGN_HOLD_DAYS = 14;

    protected $fillable = [
        'property_id', 'from_user_id', 'to_user_id', 'to_email', 'status',
        'expires_at', 'decided_at', 'from_ip', 'decided_ip', 'moved',
        'notice_status', 'notice_cursor', 'notice_total', 'notice_sent', 'hold_until',
        'completes_at', 'completed_at', 'cancelled_at', 'cancelled_by', 'held_at', 'held_by', 'admin_note',
        'device_takeover', 'device_count', 'device_balance_kes', 'device_monthly_kes',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'decided_at' => 'datetime',
        'moved' => 'array',
        'hold_until' => 'datetime',
        'completes_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'held_at' => 'datetime',
        'device_takeover' => 'boolean',
        'device_count' => 'integer',
        'device_balance_kes' => 'integer',
        'device_monthly_kes' => 'integer',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending' && $this->expires_at->isFuture();
    }

    /** Accepted, waiting out the cooling-off before the hand-over. */
    public function isCoolingOff(): bool
    {
        return $this->status === 'accepted';
    }

    /** Still open: waiting for an answer, or accepted and cooling off. The owner can cancel it. */
    public function isOpen(): bool
    {
        return $this->isPending() || $this->isCoolingOff();
    }

    /** Whether this user is the one invited (signed in with the invited email). */
    public function isFor(User $user): bool
    {
        return static::normalizeEmail($user->email) === $this->to_email;
    }

    /** @param  Builder<PropertyTransfer>  $query */
    public function scopeIncomingFor(Builder $query, User $user): Builder
    {
        return $query->where(fn ($q) => $q->where('to_email', static::normalizeEmail($user->email))->orWhere('to_user_id', $user->id));
    }

    /** Pending transfers past their date become "expired". */
    public static function expireStale(): void
    {
        static::where('status', 'pending')->where('expires_at', '<=', now())->update(['status' => 'expired']);
    }

    public static function normalizeEmail(?string $email): string
    {
        return mb_strtolower(trim((string) $email));
    }
}
