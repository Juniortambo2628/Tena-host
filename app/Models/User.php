<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, \Laravel\Cashier\Billable, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'first_name',
        'last_name',
        'avatar_path',
        'email',
        'password',
        'role',
        'phone_number',
        'two_factor_enabled',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
        'last_login',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'last_login' => 'datetime',
        'two_factor_enabled' => 'boolean',
        'two_factor_secret' => 'encrypted',
        'two_factor_recovery_codes' => 'encrypted:array',
        'two_factor_confirmed_at' => 'datetime',
    ];

    /**
     * Get the properties owned by the user (Superhost).
     */
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
     * Get cached property IDs owned by this user.
     */
    public function propertyIds(): array
    {
        if ($this->isAdmin()) {
            return Cache::remember('admin_property_ids', 300, fn () => Property::pluck('id')->toArray());
        }

        return Cache::remember("user_property_ids_{$this->id}", 300, fn () => $this->properties()->pluck('id')->toArray());
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
