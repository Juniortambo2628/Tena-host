<?php

namespace App\Services;

use App\Mail\TransferNoticeMail;
use App\Models\Guest;
use App\Models\PropertyTransfer;
use App\Services\Messaging\Messenger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Telling a transferred property's guests who runs it now
 * (docs/plans/property-transfer.md, step 2). One short note per guest, in the
 * venue's name: by email when they gave one (with an unsubscribe link), else
 * on WhatsApp ("reply STOP"). Sent in batches by `transfers:notices`, which
 * picks up where it left off.
 */
class TransferNoticeService
{
    public const BATCH = 200;

    public function __construct(protected Messenger $messenger, protected ReviewRequestService $reviews) {}

    /**
     * The guests to tell: contactable (agreed at WiFi login, or a booking), not
     * opted out, and reachable by email or phone.
     *
     * @return Builder<Guest>
     */
    public static function recipients(PropertyTransfer $transfer): Builder
    {
        return Guest::where('property_id', $transfer->property_id)
            ->whereNull('opted_out_at')
            ->where(fn ($q) => $q->whereNotNull('consented_at')->orWhereNotNull('check_out'))
            ->where(fn ($q) => $q->where(fn ($q) => $q->whereNotNull('email')->where('email', '!=', ''))->orWhereNotNull('phone'));
    }

    /** Send the next batch for one transfer; returns how many were sent. */
    public function sendBatch(PropertyTransfer $transfer, int $limit = self::BATCH): int
    {
        $transfer->loadMissing('property', 'toUser');
        $guests = static::recipients($transfer)->where('id', '>', $transfer->notice_cursor)->orderBy('id')->limit($limit)->get();

        $sent = 0;
        foreach ($guests as $guest) {
            $guest->setRelation('property', $transfer->property);
            if ($this->send($guest, $transfer)) {
                $sent++;
            }
            $transfer->notice_cursor = $guest->id;
        }

        $transfer->notice_sent += $sent;
        if ($guests->count() < $limit) {
            $transfer->notice_status = 'sent';
        }
        $transfer->save();

        return $sent;
    }

    public function send(Guest $guest, PropertyTransfer $transfer): bool
    {
        $newOwner = static::newOwnerName($transfer);

        if (filled($guest->email)) {
            // The unsubscribe link is the review link's signed one: it needs the guest's token.
            $this->reviews->link($guest);
            try {
                Mail::to($guest->email)->send(new TransferNoticeMail($guest, $newOwner));

                return true;
            } catch (\Throwable $e) {
                Log::warning('Transfer notice email failed', ['guest' => $guest->id, 'error' => $e->getMessage()]);

                return false;
            }
        }

        return $this->messenger->toGuest($guest, 'whatsapp', static::whatsappText($guest, $newOwner))['success'];
    }

    public static function newOwnerName(PropertyTransfer $transfer): string
    {
        $user = $transfer->toUser;

        return trim("{$user?->first_name} {$user?->last_name}") ?: 'a new owner';
    }

    public static function whatsappText(Guest $guest, string $newOwner): string
    {
        $property = $guest->property->name;

        return "Hi {$guest->first_name}, a quick note from {$property}: it's now run by {$newOwner}. "
            ."They'll look after the details you shared with us and may send you {$property}'s news and offers, as before. "
            .'Reply STOP if you would rather not hear from us.';
    }
}
