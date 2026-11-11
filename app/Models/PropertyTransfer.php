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

    protected $fillable = [
        'property_id', 'from_user_id', 'to_user_id', 'to_email', 'status',
        'expires_at', 'decided_at', 'from_ip', 'decided_ip', 'moved',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'decided_at' => 'datetime',
        'moved' => 'array',
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
