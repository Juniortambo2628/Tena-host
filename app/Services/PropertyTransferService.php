<?php

namespace App\Services;

use App\Mail\TransferUpdateMail;
use App\Models\Campaign;
use App\Models\DeviceAgreement;
use App\Models\Event;
use App\Models\Guest;
use App\Models\Property;
use App\Models\PropertyTransfer;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

/**
 * Handing a property to another TenaFi account (docs/plans/property-transfer.md).
 *
 * 1. The owner offers it to an email (start). Both sides are told, by in-app
 *    notification and email; the owner's email has an "It wasn't me" link
 *    that cancels without signing in.
 * 2. The recipient, signed in with that email, accepts or declines. Accepting
 *    starts a 48-hour cooling-off ("accepted"): the owner can still cancel,
 *    and a TenaFi admin can hold or cancel it.
 * 3. `transfers:complete` then hands it over (complete) in one transaction:
 *    the property, its campaigns and events move; guests, devices, visits,
 *    orders and amenities follow the property; the old owner's team loses
 *    access; a PMS connection is marked disconnected. The guests are told who
 *    runs it now and held from messages for 14 days (TransferNoticeService).
 *    The WiFi keeps running throughout.
 *
 * Not yet: taking over a device still being paid off (it blocks the transfer).
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
        if (PropertyTransfer::where('property_id', $property->id)->whereIn('status', ['pending', 'accepted'])->exists()) {
            throw ValidationException::withMessages(['property_id' => 'This property already has a transfer under way. Cancel it first.']);
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
        $ownerName = static::name($owner);

        NotificationService::systemAlert(
            'Transfer started',
            "You offered {$property->name} to {$email}. If this wasn't you, cancel it now on Properties → Transfers.",
            $owner->id,
        );
        $this->email($owner->email, $owner->first_name, 'You offered a property', [
            "You offered {$property->name} to {$email}. Nothing moves until they accept, and you can cancel until then.",
            'If they accept, there are '.PropertyTransfer::COOLING_OFF_HOURS.' more hours in which you can still cancel before it becomes theirs.',
        ], ['label' => 'Open Transfers', 'url' => route('host.transfers.index')], $this->cancelLink($transfer, $transfer->expires_at));

        if ($recipient = $this->recipientAccount($email)) {
            NotificationService::systemAlert(
                'A property is being transferred to you',
                "{$ownerName} wants to hand {$property->name} to you. Open Properties → Transfers to accept or decline.",
                $recipient->id,
            );
        }
        $this->email($email, $recipient?->first_name, 'A property is being handed to you', [
            "{$ownerName} wants to hand {$property->name} to you on TenaFi, with its WiFi page, devices and guest list.",
            $recipient
                ? 'Sign in and open Properties → Transfers to accept or decline. The offer is open until '.static::when($transfer->expires_at).'.'
                : 'To accept, sign up for TenaFi with this email address, then open Properties → Transfers. The offer is open until '.static::when($transfer->expires_at).'.',
        ], ['label' => 'Review the offer', 'url' => route('host.transfers.index')]);

        return $transfer;
    }

    /** The recipient accepts: the cooling-off starts, and the hand-over follows unless it's cancelled. */
    public function accept(PropertyTransfer $transfer, User $recipient, ?string $ip = null): PropertyTransfer
    {
        $transfer = DB::transaction(function () use ($transfer, $recipient, $ip) {
            $transfer = PropertyTransfer::whereKey($transfer->id)->lockForUpdate()->firstOrFail();
            $property = Property::findOrFail($transfer->property_id);

            $this->ensureCanDecide($transfer, $recipient);
            if ($property->user_id !== $transfer->from_user_id) {
                throw ValidationException::withMessages(['transfer' => 'This property has changed hands since the offer was made.']);
            }
            $this->ensureNoOpenDevices($property, 'transfer');

            $transfer->update([
                'status' => 'accepted',
                'to_user_id' => $recipient->id,
                'decided_at' => now(),
                'decided_ip' => $ip,
                'completes_at' => now()->addHours(PropertyTransfer::COOLING_OFF_HOURS),
            ]);

            return $transfer;
        });

        $transfer->load('property', 'fromUser');
        $name = $transfer->property->name;
        $on = static::when($transfer->completes_at);
        NotificationService::systemAlert('Transfer accepted', "You accepted {$name}. It becomes yours on {$on}, after a ".PropertyTransfer::COOLING_OFF_HOURS.'-hour cooling-off.', $recipient->id);
        NotificationService::systemAlert('Transfer accepted', "{$transfer->to_email} accepted {$name}. It becomes theirs on {$on}; you can still cancel until then.", $transfer->from_user_id);
        $this->email($transfer->fromUser->email, $transfer->fromUser->first_name, 'Your property transfer was accepted', [
            "{$transfer->to_email} accepted {$name}. It becomes theirs on {$on}.",
            'Until then you can still cancel it, and nothing has moved yet.',
        ], ['label' => 'Open Transfers', 'url' => route('host.transfers.index')], $this->cancelLink($transfer, $transfer->completes_at));
        $this->email($recipient->email, $recipient->first_name, 'You accepted a property', [
            "You accepted {$name}. It becomes yours on {$on}, after a ".PropertyTransfer::COOLING_OFF_HOURS.'-hour cooling-off in which the current owner can still cancel.',
            'Its guests will get a short note saying you run it now, and you can send them campaigns '.PropertyTransfer::CAMPAIGN_HOLD_DAYS.' days after that.',
        ], ['label' => 'Open Transfers', 'url' => route('host.transfers.index')]);

        return $transfer;
    }

    /**
     * The hand-over, once the cooling-off has passed (transfers:complete).
     * Returns what moved, or null when it isn't due, is held, or no longer can go ahead.
     *
     * @return array<string, int>|null
     */
    public function complete(PropertyTransfer $transfer): ?array
    {
        $result = DB::transaction(function () use ($transfer) {
            $transfer = PropertyTransfer::whereKey($transfer->id)->lockForUpdate()->firstOrFail();
            if ($transfer->status !== 'accepted' || $transfer->held_at || ! $transfer->completes_at || $transfer->completes_at->isFuture()) {
                return null;
            }
            $property = Property::whereKey($transfer->property_id)->lockForUpdate()->firstOrFail();
            $recipient = User::find($transfer->to_user_id);

            // Things may have changed during the cooling-off.
            $problem = match (true) {
                ! $recipient => 'the new owner’s account no longer exists',
                $property->user_id !== $transfer->from_user_id => 'the property changed hands in the meantime',
                DeviceAgreement::where('property_id', $property->id)->whereIn('status', self::OPEN_DEVICE_STATUSES)->exists() => 'a device at the property is now being paid off monthly',
                default => null,
            };
            if ($problem) {
                $transfer->update(['status' => 'cancelled', 'cancelled_at' => now(), 'cancelled_by' => 'system', 'admin_note' => "Not completed: {$problem}."]);

                return ['cancelled' => $problem, 'transfer' => $transfer];
            }

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

            // The guests who came with it are told who runs it now (transfers:notices),
            // and the new owner can't message them for CAMPAIGN_HOLD_DAYS, so they can opt out.
            $holdUntil = now()->addDays(PropertyTransfer::CAMPAIGN_HOLD_DAYS);
            Guest::where('property_id', $property->id)->update(['campaign_hold_until' => $holdUntil]);

            $transfer->update(['status' => 'completed', 'completed_at' => now(), 'moved' => $moved, 'hold_until' => $holdUntil]);
            $noticeTotal = TransferNoticeService::recipients($transfer)->count();
            $transfer->update(['notice_total' => $noticeTotal, 'notice_status' => $noticeTotal > 0 ? 'pending' : 'none']);

            return ['moved' => $moved, 'transfer' => $transfer];
        });

        if (! $result) {
            return null;
        }
        $transfer = $result['transfer']->load('property', 'fromUser', 'toUser');
        $name = $transfer->property->name;

        if (isset($result['cancelled'])) {
            foreach (array_filter([$transfer->fromUser, $transfer->toUser]) as $user) {
                NotificationService::systemAlert('Transfer not completed', "{$name} didn't move: {$result['cancelled']}.", $user->id);
                $this->email($user->email, $user->first_name, 'A property transfer didn’t go ahead', ["{$name} didn't move to its new owner: {$result['cancelled']}. It stays where it was."]);
            }

            return null;
        }

        $from = static::when($transfer->hold_until);
        NotificationService::systemAlert('Transfer complete', "{$name} is now yours, with its WiFi page, devices and guest list. Its guests are told you run it now; you can send them campaigns from {$from}.", $transfer->to_user_id);
        NotificationService::systemAlert('Transfer complete', "{$name} now belongs to {$transfer->to_email}.", $transfer->from_user_id);
        $this->email($transfer->toUser->email, $transfer->toUser->first_name, 'The property is now yours', [
            "{$name} is now in your TenaFi account, with its WiFi page, devices and guest list. The WiFi kept running throughout.",
            "Its guests are getting a short note saying you run it now. You can send them campaigns from {$from}. If it used a booking system (PMS), reconnect yours on the property page.",
        ], ['label' => 'Open your properties', 'url' => route('host.properties.index')]);
        $this->email($transfer->fromUser->email, $transfer->fromUser->first_name, 'Your property transfer is complete', [
            "{$name} now belongs to {$transfer->to_email} and is no longer in your account.",
        ]);

        return $result['moved'];
    }

    public function decline(PropertyTransfer $transfer, User $recipient, ?string $ip = null): void
    {
        $this->ensureCanDecide($transfer, $recipient);
        $transfer->update(['status' => 'declined', 'to_user_id' => $recipient->id, 'decided_at' => now(), 'decided_ip' => $ip]);
        NotificationService::systemAlert('Transfer declined', "{$transfer->to_email} declined {$transfer->property->name}. It stays yours.", $transfer->from_user_id);
        $this->email($transfer->fromUser->email, $transfer->fromUser->first_name, 'Your property transfer was declined', ["{$transfer->to_email} declined {$transfer->property->name}. It stays yours."]);
    }

    /** The owner cancels, signed in: while it waits for an answer or cools off. */
    public function cancel(PropertyTransfer $transfer, User $owner, ?string $ip = null): void
    {
        if ($transfer->from_user_id !== $owner->id) {
            abort(403);
        }
        $this->cancelAs($transfer, 'owner', null, $ip);
    }

    /** The owner's "It wasn't me" link in an email, without signing in. */
    public function cancelFromLink(PropertyTransfer $transfer, ?string $ip = null): void
    {
        $this->cancelAs($transfer, 'link', 'Cancelled from the link in the owner’s email.', $ip);
    }

    public function adminCancel(PropertyTransfer $transfer, User $admin, ?string $note): void
    {
        $this->cancelAs($transfer, 'admin', trim(($note ?: 'Cancelled by TenaFi.').' ('.static::name($admin).')'));
    }

    /** A TenaFi admin pauses the hand-over (e.g. the owner says it wasn't them). */
    public function hold(PropertyTransfer $transfer, User $admin, ?string $note): void
    {
        if (! $transfer->isCoolingOff()) {
            throw ValidationException::withMessages(['transfer' => 'Only an accepted transfer in its cooling-off can be held.']);
        }
        $transfer->update(['held_at' => now(), 'held_by' => $admin->id, 'admin_note' => $note ?: 'Held by TenaFi.']);
    }

    /** Lets a held transfer go ahead (straight away if its cooling-off has passed). */
    public function release(PropertyTransfer $transfer): void
    {
        if (! $transfer->held_at) {
            throw ValidationException::withMessages(['transfer' => 'This transfer isn’t held.']);
        }
        $transfer->update(['held_at' => null, 'held_by' => null]);
    }

    /** The account that would receive it: a host account owner with that email, if there is one. */
    public function recipientAccount(string $email): ?User
    {
        return User::whereRaw('LOWER(email) = ?', [PropertyTransfer::normalizeEmail($email)])
            ->where('role', 'host')->whereNull('team_owner_id')->first();
    }

    /** "It wasn't me": a signed link that cancels without signing in, valid while the transfer can still be cancelled. */
    public function cancelLink(PropertyTransfer $transfer, Carbon $until): array
    {
        return ['label' => 'Cancel this transfer', 'url' => URL::temporarySignedRoute('transfers.cancel-link', $until->copy()->addHour(), ['transfer' => $transfer->id])];
    }

    private function cancelAs(PropertyTransfer $transfer, string $by, ?string $note, ?string $ip = null): void
    {
        $transfer = DB::transaction(function () use ($transfer, $by, $note, $ip) {
            $transfer = PropertyTransfer::whereKey($transfer->id)->lockForUpdate()->firstOrFail();
            if (! $transfer->isOpen()) {
                throw ValidationException::withMessages(['transfer' => 'This transfer can no longer be cancelled.']);
            }
            $transfer->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => $by,
                'admin_note' => $note ?? $transfer->admin_note,
                'decided_ip' => $transfer->decided_ip ?? $ip,
            ]);

            return $transfer;
        });

        $transfer->load('property', 'fromUser', 'toUser');
        $name = $transfer->property->name;
        NotificationService::systemAlert('Transfer cancelled', "The transfer of {$name} to {$transfer->to_email} was cancelled. It stays yours.", $transfer->from_user_id);
        $this->email($transfer->fromUser->email, $transfer->fromUser->first_name, 'Your property transfer was cancelled', [
            "The transfer of {$name} to {$transfer->to_email} was cancelled. It stays yours, and nothing moved.",
            $by === 'link' ? 'It was cancelled from the link in our email. If you didn’t offer this transfer, change your password now.' : '',
        ]);
        if ($transfer->toUser) {
            NotificationService::systemAlert('Transfer cancelled', "The transfer of {$name} to you was cancelled.", $transfer->toUser->id);
        }
        $this->email($transfer->to_email, $transfer->toUser?->first_name, 'A property transfer was cancelled', ["The transfer of {$name} to you was cancelled. Nothing changes in your account."]);
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

    /**
     * @param  list<string>  $lines
     * @param  array{label: string, url: string}|null  $button
     * @param  array{label: string, url: string}|null  $cancel
     */
    private function email(string $to, ?string $name, string $heading, array $lines, ?array $button = null, ?array $cancel = null): void
    {
        try {
            Mail::to($to)->send(new TransferUpdateMail($heading, array_values(array_filter($lines)), $button, $cancel, $name));
        } catch (\Throwable $e) {
            Log::warning('Property transfer email failed', ['to' => $to, 'heading' => $heading, 'error' => $e->getMessage()]);
        }
    }

    private static function name(User $user): string
    {
        return trim("{$user->first_name} {$user->last_name}") ?: $user->email;
    }

    private static function when(?Carbon $at): string
    {
        return $at ? $at->copy()->timezone('Africa/Nairobi')->format('j M, H:i') : '';
    }
}
