<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\DeviceAgreement;
use App\Models\Event;
use App\Models\Property;
use App\Models\PropertyTransfer;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Handing a property to another TenaFi account (docs/plans/property-transfer.md,
 * step 1). The owner starts it; the recipient, signed in with the invited
 * email, accepts or declines; the owner can cancel while it waits. On
 * acceptance everything linked to the property moves with it in one
 * transaction: its WiFi page, devices, guests, visits, orders, amenities,
 * campaigns and events. The old owner's team loses access, and a PMS
 * connection is marked disconnected for the new owner to reconnect. The WiFi
 * keeps running: devices and the WiFi page aren't touched.
 *
 * Not yet (later steps): telling guests and holding campaigns for 14 days,
 * a 48-hour cooling-off, emails, and taking over a device still being paid off.
 */
class PropertyTransferService
{
    /** A device agreement in one of these states blocks a transfer: it isn't settled yet. */
    public const OPEN_DEVICE_STATUSES = ['active', 'overdue', 'suspended', 'return_requested'];

    /** Admin -> Settings "Property transfers" (starts off). */
    public static function enabled(): bool
    {
        return filter_var(Setting::getValue('property_transfers_enabled', '0'), FILTER_VALIDATE_BOOLEAN);
    }

    public function start(User $owner, Property $property, string $email, ?string $ip = null): PropertyTransfer
    {
        $email = PropertyTransfer::normalizeEmail($email);

        if ($property->user_id !== $owner->id || $owner->isTeamMember()) {
            throw ValidationException::withMessages(['property_id' => 'Only the property’s owner can transfer it.']);
        }
        if ($email === PropertyTransfer::normalizeEmail($owner->email)) {
            throw ValidationException::withMessages(['email' => 'That’s your own email. Enter the new owner’s.']);
        }
        PropertyTransfer::expireStale();
        if (PropertyTransfer::where('property_id', $property->id)->where('status', 'pending')->exists()) {
            throw ValidationException::withMessages(['property_id' => 'This property already has a transfer waiting. Cancel it first.']);
        }
        $this->ensureNoOpenDevices($property, 'property_id');

        $transfer = PropertyTransfer::create([
            'property_id' => $property->id,
            'from_user_id' => $owner->id,
            'to_email' => $email,
            'status' => 'pending',
            'expires_at' => now()->addDays(PropertyTransfer::VALID_DAYS),
            'from_ip' => $ip,
        ]);

        NotificationService::systemAlert(
            'Transfer started',
            "You offered {$property->name} to {$email}. If this wasn't you, cancel it now on Properties → Transfers.",
            $owner->id,
        );
        if ($recipient = $this->recipientAccount($email)) {
            NotificationService::systemAlert(
                'A property is being transferred to you',
                trim("{$owner->first_name} {$owner->last_name}")." wants to hand {$property->name} to you. Open Properties → Transfers to accept or decline.",
                $recipient->id,
            );
        }

        return $transfer;
    }

    /**
     * Move the property and everything linked to it to the recipient.
     *
     * @return array<string, int> counts of what moved
     */
    public function accept(PropertyTransfer $transfer, User $recipient, ?string $ip = null): array
    {
        return DB::transaction(function () use ($transfer, $recipient, $ip) {
            $transfer = PropertyTransfer::whereKey($transfer->id)->lockForUpdate()->firstOrFail();
            $property = Property::whereKey($transfer->property_id)->lockForUpdate()->firstOrFail();

            $this->ensureCanDecide($transfer, $recipient);
            if ($property->user_id !== $transfer->from_user_id) {
                throw ValidationException::withMessages(['transfer' => 'This property has changed hands since the offer was made.']);
            }
            $this->ensureNoOpenDevices($property, 'transfer');

            $moved = [
                'guests' => $property->guests()->count(),
                'access_points' => $property->accessPoints()->count(),
                'campaigns' => Campaign::where('property_id', $property->id)->count(),
                'events' => Event::where('property_id', $property->id)->count(),
            ];

            $property->forceFill([
                'user_id' => $recipient->id,
                // The old owner's PMS account stops syncing; the new owner reconnects theirs.
                'pms_connection_status' => $property->pms_integration_type ? 'disconnected' : $property->pms_connection_status,
            ])->save();
            Campaign::where('property_id', $property->id)->update(['user_id' => $recipient->id]);
            Event::where('property_id', $property->id)->update(['user_id' => $recipient->id]);
            // The old owner's team members lose access to this property, straight away
            // (their list of properties is cached; the owners' caches clear when the property saves).
            $members = DB::table('staff_property')->where('property_id', $property->id)->pluck('user_id');
            DB::table('staff_property')->where('property_id', $property->id)->delete();
            $members->each(fn ($id) => Cache::forget("user_property_ids_{$id}"));

            $transfer->update([
                'status' => 'completed',
                'to_user_id' => $recipient->id,
                'decided_at' => now(),
                'decided_ip' => $ip,
                'moved' => $moved,
            ]);

            NotificationService::systemAlert('Transfer complete', "{$property->name} is now yours, with its WiFi page, devices and guest list.", $recipient->id);
            NotificationService::systemAlert('Transfer complete', "{$property->name} now belongs to {$transfer->to_email}.", $transfer->from_user_id);

            return $moved;
        });
    }

    public function decline(PropertyTransfer $transfer, User $recipient, ?string $ip = null): void
    {
        $this->ensureCanDecide($transfer, $recipient);
        $transfer->update(['status' => 'declined', 'to_user_id' => $recipient->id, 'decided_at' => now(), 'decided_ip' => $ip]);
        NotificationService::systemAlert('Transfer declined', "{$transfer->to_email} declined {$transfer->property->name}. It stays yours.", $transfer->from_user_id);
    }

    public function cancel(PropertyTransfer $transfer, User $owner, ?string $ip = null): void
    {
        if ($transfer->from_user_id !== $owner->id) {
            abort(403);
        }
        if (! $transfer->isPending()) {
            throw ValidationException::withMessages(['transfer' => 'This transfer is no longer waiting.']);
        }
        $transfer->update(['status' => 'cancelled', 'decided_at' => now(), 'decided_ip' => $ip]);
    }

    /** The account that would receive it: a host account owner with that email, if there is one. */
    public function recipientAccount(string $email): ?User
    {
        return User::whereRaw('LOWER(email) = ?', [PropertyTransfer::normalizeEmail($email)])
            ->where('role', 'host')->whereNull('team_owner_id')->first();
    }

    private function ensureCanDecide(PropertyTransfer $transfer, User $recipient): void
    {
        if (! $transfer->isFor($recipient) || $recipient->id === $transfer->from_user_id) {
            abort(403);
        }
        if ($recipient->role !== 'host' || $recipient->isTeamMember()) {
            throw ValidationException::withMessages(['transfer' => 'Accept it from your own TenaFi host account, not a team member’s.']);
        }
        if (! $transfer->isPending()) {
            throw ValidationException::withMessages(['transfer' => 'This transfer is no longer waiting.']);
        }
    }

    private function ensureNoOpenDevices(Property $property, string $field): void
    {
        if (DeviceAgreement::where('property_id', $property->id)->whereIn('status', self::OPEN_DEVICE_STATUSES)->exists()) {
            throw ValidationException::withMessages([$field => 'A TenaFi device at this property is still being paid off monthly. It must be paid off before the property can move.']);
        }
    }
}
