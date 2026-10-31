<?php

namespace App\Models;

use App\Support\TrialSlots;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Registration extends Model
{
    use HasFactory;

    public const TYPE_HOST = 'host';

    public const TYPE_BUSINESS = 'business';

    /** Event quote requests from /events (not free-trial sign-ups). */
    public const TYPE_EVENT = 'event';

    public const TYPES = [self::TYPE_HOST, self::TYPE_BUSINESS, self::TYPE_EVENT];

    /** The free trial is for rentals and businesses; event quotes don't take a slot. */
    public const TRIAL_TYPES = [self::TYPE_HOST, self::TYPE_BUSINESS];

    protected $fillable = [
        'type',
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
        'business_name',
        'answers',
        'consent_text',
        'consented_at',
        'consent_ip',
        'source_page',
        'user_id',
    ];

    protected $casts = [
        'property_count' => 'integer',
        'agree_updates' => 'boolean',
        'answers' => 'array',
        'consented_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // Every sign-up is a trial booking; the public slot counter must see it straight away.
        static::saved(fn () => TrialSlots::forget());
        static::deleted(fn () => TrialSlots::forget());
    }
}
