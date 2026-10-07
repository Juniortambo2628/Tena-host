<?php

namespace App\Services;

use App\Models\Guest;
use App\Models\Property;
use App\Services\Messaging\Messenger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * After a stay or visit, thank the guest and ask for a review (WhatsApp,
 * falling back to SMS). Each guest is asked once per property.
 *
 * A stay ends at check-out (PMS guests, assumed 11:00) or, without a
 * booking, at the guest's last WiFi connection. The request goes out once
 * the property's delay has passed since then. Stays that ended more than a
 * week ago are skipped, so switching the feature on doesn't message
 * everyone in the guest book.
 */
class ReviewRequestService
{
    public const DEFAULT_MESSAGE = 'Hi {guest_name}, thank you for staying at {property_name}! If you have a minute, a review would mean a lot to us: {review_link}';

    public const DEFAULT_BUSINESS_MESSAGE = 'Hi {guest_name}, thanks for visiting {property_name} today! How did we do? It takes 30 seconds to share your experience on Google: {review_link}';

    public static function defaultMessage(Property $property): string
    {
        return $property->host?->isBusiness() ? self::DEFAULT_BUSINESS_MESSAGE : self::DEFAULT_MESSAGE;
    }

    private const CHECKOUT_HOUR = 11;

    private const LOOKBACK_DAYS = 7;

    public function __construct(protected Messenger $messenger) {}

    /**
     * Send every request that is due. Returns how many were sent.
     */
    public function sendDue(): int
    {
        $sent = 0;

        Property::where('review_requests_enabled', true)
            ->whereNotNull('review_url')
            ->each(function (Property $property) use (&$sent) {
                $this->due($property)->each(function (Guest $guest) use ($property, &$sent) {
                    $guest->setRelation('property', $property);
                    $sent += (int) $this->send($guest);
                });
            });

        return $sent;
    }

    /**
     * Guests of this property whose review request is due now.
     *
     * @return Builder<Guest>
     */
    public function due(Property $property): Builder
    {
        $endedBefore = now()->subHours($property->review_request_delay_hours);
        $endedAfter = now()->subDays(self::LOOKBACK_DAYS);
        // A check-out date counts as ending at CHECKOUT_HOUR that day.
        $checkoutBefore = $endedBefore->copy()->subHours(self::CHECKOUT_HOUR)->toDateString();

        return Guest::where('property_id', $property->id)
            ->whereNull('review_requested_at')
            ->whereNotNull('phone')
            // WiFi guests agreed to be asked; PMS guests have a booking with the host.
            ->where(fn ($q) => $q->whereNotNull('consented_at')->orWhereNotNull('check_out'))
            ->where(fn ($q) => $q
                ->where(fn ($q) => $q->whereNotNull('check_out')
                    ->whereDate('check_out', '<=', $checkoutBefore)
                    ->whereDate('check_out', '>=', $endedAfter->toDateString()))
                ->orWhere(fn ($q) => $q->whereNull('check_out')
                    ->whereBetween('last_connected', [$endedAfter, $endedBefore])));
    }

    public function send(Guest $guest): bool
    {
        $property = $guest->property;
        $message = strtr($property->review_message ?: self::defaultMessage($property), [
            '{review_link}' => $this->link($guest),
        ]);

        $result = $this->messenger->toGuest($guest, 'whatsapp', $message);

        if ($result['success']) {
            $guest->forceFill(['review_requested_at' => now()])->save();
        }

        return $result['success'];
    }

    /**
     * A short link through TenaFi, so clicks are counted before the guest
     * lands on the host's review page.
     */
    public function link(Guest $guest): string
    {
        if (! $guest->review_token) {
            do {
                $token = Str::random(8);
            } while (Guest::where('review_token', $token)->exists());

            $guest->forceFill(['review_token' => $token])->save();
        }

        return route('reviews.go', $guest->review_token);
    }

    /**
     * Record the click and return where to send the guest.
     */
    public function click(Guest $guest): string
    {
        if (! $guest->review_clicked_at) {
            $guest->forceFill(['review_clicked_at' => now()])->save();
        }

        return $guest->property->review_url;
    }
}
