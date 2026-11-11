<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * A TenaFi device paid for monthly (docs/plans/device-monthly-payments.md):
 * the client, site and device, its instalments and its log. Step 1 of the
 * plan: recorded and kept up to date by hand in Admin -> Devices.
 */
class DeviceAgreement extends Model
{
    public const STATUSES = [
        'active' => 'Active',
        'overdue' => 'Overdue',
        'suspended' => 'Suspended',
        'return_requested' => 'Return requested',
        'returned' => 'Returned',
        'paid_off' => 'Paid off',
        'written_off' => 'Written off',
        'lost_or_stolen' => 'Lost or stolen',
    ];

    protected $fillable = [
        'user_id', 'property_id', 'access_point_id', 'device_type', 'serial_number', 'mac_address',
        'price_kes', 'monthly_kes', 'months', 'starts_on', 'status', 'paid_off_at', 'notes',
    ];

    protected $casts = [
        'starts_on' => 'date',
        'paid_off_at' => 'datetime',
        'price_kes' => 'integer',
        'monthly_kes' => 'integer',
        'months' => 'integer',
    ];

    /** Admin -> Settings "Device register": shows Admin -> Devices in the menu. */
    public static function enabled(): bool
    {
        return filter_var(Setting::getValue('device_financing_enabled', '0'), FILTER_VALIDATE_BOOLEAN);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function accessPoint(): BelongsTo
    {
        return $this->belongsTo(AccessPoint::class);
    }

    public function instalments(): HasMany
    {
        return $this->hasMany(DeviceInstalment::class)->orderBy('number');
    }

    public function events(): HasMany
    {
        return $this->hasMany(DeviceEvent::class)->latest('id');
    }

    /**
     * Record a device and its instalment schedule: one per month from the
     * installation day (the first is due that day).
     *
     * @param  array<string, mixed>  $data
     */
    public static function record(array $data, ?User $actor = null): self
    {
        return DB::transaction(function () use ($data, $actor) {
            $agreement = static::create($data + ['status' => 'active']);
            $start = Carbon::parse($agreement->starts_on);
            for ($n = 1; $n <= $agreement->months; $n++) {
                $agreement->instalments()->create([
                    'number' => $n,
                    // No overflow: a device installed on 31 January is next due on 28/29 February.
                    'due_on' => $start->copy()->addMonthsNoOverflow($n - 1),
                    'amount_kes' => $agreement->monthly_kes,
                ]);
            }
            $agreement->log('recorded', null, $actor, ['months' => $agreement->months, 'monthly_kes' => $agreement->monthly_kes]);

            return $agreement;
        });
    }

    /** What's still to pay on the device. */
    public function balance(): int
    {
        return $this->instalments->sum(fn (DeviceInstalment $i) => $i->remaining());
    }

    public function nextDue(): ?DeviceInstalment
    {
        return $this->instalments->first(fn (DeviceInstalment $i) => ! $i->isSettled());
    }

    /** Days since the oldest unpaid instalment fell due (0 when nothing is late). */
    public function daysOverdue(?Carbon $today = null): int
    {
        $today = ($today ?? now())->copy()->startOfDay();
        $next = $this->nextDue();

        return $next && $next->due_on->lt($today) ? (int) $next->due_on->diffInDays($today) : 0;
    }

    /** A payment against one instalment (part or all of it). Settles the agreement after the last one. */
    public function recordPayment(DeviceInstalment $instalment, int $amount, ?string $reference, ?User $actor = null): void
    {
        DB::transaction(function () use ($instalment, $amount, $reference, $actor) {
            $paid = min($instalment->amount_kes, $instalment->paid_kes + $amount);
            $instalment->update([
                'paid_kes' => $paid,
                'paid_at' => now(),
                'reference' => $reference ?: $instalment->reference,
                'status' => $paid >= $instalment->amount_kes ? 'paid' : 'partly_paid',
            ]);
            $this->log('payment', $reference, $actor, ['instalment' => $instalment->number, 'amount_kes' => $amount]);

            $this->load('instalments');
            if ($this->balance() === 0 && $this->status !== 'paid_off') {
                $this->update(['status' => 'paid_off', 'paid_off_at' => now()]);
                $this->log('paid_off', 'Last instalment paid: the device is the client’s.', $actor);
            }
        });
    }

    public function changeStatus(string $status, ?string $note, ?User $actor = null): void
    {
        $from = $this->status;
        $this->update(['status' => $status, 'paid_off_at' => $status === 'paid_off' ? ($this->paid_off_at ?? now()) : $this->paid_off_at]);
        $this->log('status', $note, $actor, ['from' => $from, 'to' => $status]);
    }

    /** @param  array<string, mixed>  $meta */
    public function log(string $type, ?string $note, ?User $actor = null, array $meta = []): DeviceEvent
    {
        return $this->events()->create(['type' => $type, 'note' => $note, 'user_id' => $actor?->id, 'meta' => $meta ?: null]);
    }
}
