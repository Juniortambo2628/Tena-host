<?php

namespace App\Services;

use App\Mail\ReviewRequestMail;
use App\Models\Guest;
use App\Models\Property;
use App\Services\Messaging\Messenger;
use App\Support\SplashDesign;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * After a stay or visit, thank the guest and ask for a review: on WhatsApp
 * (falling back to SMS), by a branded email from the property, or both
 * (email when the guest gave one, else WhatsApp), as the property chooses.
 * Each guest is asked once per property.
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
        return SplashDesign::isBusiness($property) ? self::DEFAULT_BUSINESS_MESSAGE : self::DEFAULT_MESSAGE;
    }

    private const CHECKOUT_HOUR = 11;

    private const LOOKBACK_DAYS = 7;

    public const CHANNELS = ['whatsapp', 'email', 'both'];

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

        return $this->askable($property)
            ->where(fn ($q) => $q
                ->where(fn ($q) => $q->whereNotNull('check_out')
                    ->whereDate('check_out', '<=', $checkoutBefore)
                    ->whereDate('check_out', '>=', $endedAfter->toDateString()))
                ->orWhere(fn ($q) => $q->whereNull('check_out')
                    ->whereBetween('last_connected', [$endedAfter, $endedBefore])));
    }

    /**
     * Everyone this property can still ask, however long ago they came: for
     * a first send to the guests captured before review requests were on.
     *
     * @return Builder<Guest>
     */
    public function everyone(Property $property): Builder
    {
        return $this->askable($property);
    }

    /** Not yet asked, agreed to be contacted, and reachable on the property's channel. */
    private function askable(Property $property): Builder
    {
        $channel = static::channel($property);

        return Guest::where('property_id', $property->id)
            ->whereNull('review_requested_at')
            ->whereNull('opted_out_at')
            // WiFi guests agreed to be asked; PMS guests have a booking with the host.
            ->where(fn ($q) => $q->whereNotNull('consented_at')->orWhereNotNull('check_out'))
            ->where(fn ($q) => match ($channel) {
                'email' => $q->whereNotNull('email')->where('email', '!=', ''),
                'both' => $q->whereNotNull('phone')->orWhere(fn ($q) => $q->whereNotNull('email')->where('email', '!=', '')),
                default => $q->whereNotNull('phone'),
            });
    }

    /** The property's channel; WhatsApp while review emails are switched off (Admin -> Settings). */
    public static function channel(Property $property): string
    {
        $channel = in_array($property->review_request_channel, self::CHANNELS, true) ? $property->review_request_channel : 'whatsapp';

        return Property::reviewEmailsEnabled() ? $channel : 'whatsapp';
    }

    /** How this guest will be asked: "email" or "whatsapp", or null when they can't be reached. */
    public function channelFor(Guest $guest): ?string
    {
        $email = filled($guest->email);

        return match (static::channel($guest->property)) {
            'email' => $email ? 'email' : null,
            'both' => $email ? 'email' : ($guest->phone ? 'whatsapp' : null),
            default => $guest->phone ? 'whatsapp' : null,
        };
    }

    public function send(Guest $guest): bool
    {
        $property = $guest->property;
        $channel = $this->channelFor($guest);

        if ($channel === 'email') {
            try {
                Mail::to($guest->email)->send(new ReviewRequestMail($guest, $this->link($guest)));
                $sent = true;
            } catch (\Throwable $e) {
                Log::warning('Review request email failed', ['guest' => $guest->id, 'error' => $e->getMessage()]);
                $sent = false;
            }
        } elseif ($channel === 'whatsapp') {
            $message = strtr($property->review_message ?: self::defaultMessage($property), [
                '{review_link}' => $this->link($guest),
            ]);
            $sent = $this->messenger->toGuest($guest, 'whatsapp', $message)['success'];
        } else {
            return false;
        }

        if ($sent) {
            $guest->forceFill(['review_requested_at' => now()])->save();
        }

        return $sent;
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
