<?php

namespace App\Models;

use App\Support\AccountType;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\PasskeyAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, \Laravel\Cashier\Billable, Notifiable, PasskeyAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    public const ACCOUNT_HOST = 'host';

    public const ACCOUNT_BUSINESS = 'business';

    /** Event organisers and venues (see App\Support\AccountType for each type's wording). */
    public const ACCOUNT_EVENT = 'event';

    public const ACCOUNT_TYPES = [self::ACCOUNT_HOST, self::ACCOUNT_BUSINESS, self::ACCOUNT_EVENT];

    protected $fillable = [
        'username',
        'first_name',
        'last_name',
        'avatar_path',
        'email',
        'password',
        'role',
        'account_profile',
        'team_owner_id',
        'team_role',
        'phone_number',
        'two_factor_enabled',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
        'last_login',
        'account_type',
        'billing_plan',
        'billing_units',
        'billing_extra_devices',
        'billing_cycle',
    ];

    // auth.user is shared with every Inertia page; the encrypted casts decrypt on serialisation.
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'last_login' => 'datetime',
        'billing_units' => 'integer',
        'billing_extra_devices' => 'integer',
        'two_factor_enabled' => 'boolean',
        'two_factor_secret' => 'encrypted',
        'two_factor_recovery_codes' => 'encrypted:array',
        'two_factor_confirmed_at' => 'datetime',
    ];

    public function getNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    /**
     * Get the properties owned by the user (Superhost).
     */
    public function isBusiness(): bool
    {
        return $this->account_type === self::ACCOUNT_BUSINESS;
    }

    public function isEventAccount(): bool
    {
        return $this->account_type === self::ACCOUNT_EVENT;
    }

    /**
     * What this account calls the people it collects and the places it runs.
     *
     * @return array{type: string, label: string, person: string, people: string, place: string, places: string, splash: string}
     */
    public function accountDefinition(): array
    {
        return AccountType::for($this->account_type);
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    /**
     * Get the guest records linked to this user account.
     */
    public function guestRecords(): HasMany
    {
        return $this->hasMany(Guest::class);
    }

    /**
     * Get the properties assigned to a staff user.
     */
    public function staffProperties(): BelongsToMany
    {
        return $this->belongsToMany(Property::class, 'staff_property');
    }

    /**
     * Check if the user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if the user is a host.
     */
    public function isHost(): bool
    {
        return $this->role === 'host';
    }

    /**
     * Check if the user is staff.
     */
    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    /**
     * Check if the user is a guest.
     */
    public function isGuest(): bool
    {
        return $this->role === 'guest';
    }

    /**
     * Scope a query to only include users of a given role.
     */
    public function scopeOfRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Get the M-Pesa transactions for the user.
     */
    public function mpesaTransactions(): HasMany
    {
        return $this->hasMany(MpesaTransaction::class);
    }

    /**
     * Get cached property IDs this user can work with: an owner's own, or for
     * a team member the owner's sites they were given (staff_property).
     */
    public function propertyIds(): array
    {
        if ($this->isAdmin()) {
            return Cache::remember('admin_property_ids', 300, fn () => Property::pluck('id')->toArray());
        }

        return Cache::remember("user_property_ids_{$this->id}", 300, fn () => $this->isTeamMember()
            ? $this->staffProperties()->where('properties.user_id', $this->team_owner_id)->pluck('properties.id')->toArray()
            : $this->properties()->pluck('id')->toArray());
    }

    /** The account owner who invited this person (their team), or null for an owner. */
    public function teamOwner(): BelongsTo
    {
        return $this->belongsTo(self::class, 'team_owner_id');
    }

    /** The people this owner invited. */
    public function teamMembers(): HasMany
    {
        return $this->hasMany(self::class, 'team_owner_id');
    }

    public function isTeamMember(): bool
    {
        return $this->team_owner_id !== null;
    }

    /** Whose account this is: the owner for a team member, else the user. Billing and plans live here. */
    public function accountOwner(): self
    {
        return $this->isTeamMember() ? ($this->teamOwner ?? $this) : $this;
    }

    /**
     * The sites (properties) this user can see: an owner's own, or a team
     * member's assigned ones. Read with this; create sites through properties().
     */
    public function sites(): Builder
    {
        return $this->isTeamMember()
            ? Property::query()->whereIn('id', $this->propertyIds())
            : $this->properties()->getQuery();
    }

    /**
     * Clear cached property IDs.
     */
    public function clearPropertyIdsCache(): void
    {
        Cache::forget("user_property_ids_{$this->id}");
        Cache::forget('admin_property_ids');
    }
}
