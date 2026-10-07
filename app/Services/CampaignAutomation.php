<?php

namespace App\Services;

use App\Jobs\SendCampaignJob;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Guest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Runs campaigns on their own (campaigns:run, every 5 minutes).
 *
 * - Broadcasts ("Custom Date", or no trigger) go to the whole audience at
 *   their send time: right away on activation when no time is set.
 * - Trigger campaigns reach each guest once, after their event plus the
 *   campaign's delay: connecting to the WiFi, 24 hours before arrival, or
 *   the day of check-out. Only events after activation count, so switching
 *   a campaign on doesn't message the whole guest book.
 */
class CampaignAutomation
{
    public const CONNECTS = 'Guest Connects to WiFi';

    public const BEFORE_ARRIVAL = '24 Hours Before Arrival';

    public const CHECKOUT_DAY = 'Day of Checkout';

    public const CUSTOM_DATE = 'Custom Date';

    /** Check-in and check-out dates are taken to happen at these hours. */
    private const CHECK_IN_HOUR = 14;

    private const CHECKOUT_MESSAGE_HOUR = 9;

    public function __construct(protected CampaignDispatcher $dispatcher) {}

    public static function isBroadcast(Campaign $campaign): bool
    {
        return in_array($campaign->trigger_event, [null, '', self::CUSTOM_DATE], true);
    }

    /**
     * Switch a campaign on. Returns how many messages were queued now.
     */
    public function activate(Campaign $campaign): int
    {
        $campaign->update(['status' => 'active', 'activated_at' => now()]);

        return static::isBroadcast($campaign) && (! $campaign->scheduled_at || $campaign->scheduled_at->isPast())
            ? $this->sendBroadcast($campaign)
            : 0;
    }

    /**
     * Send everything due. Returns how many messages were queued.
     */
    public function run(): int
    {
        $queued = 0;

        Campaign::where('status', 'active')->each(function (Campaign $campaign) use (&$queued) {
            if (static::isBroadcast($campaign)) {
                if (! $campaign->sent_at && $campaign->scheduled_at?->isPast()) {
                    $queued += $this->sendBroadcast($campaign);
                }

                return;
            }

            $queued += $this->queue($campaign, $this->dueForTrigger($campaign));
        });

        return $queued;
    }

    public function sendBroadcast(Campaign $campaign): int
    {
        $queued = $this->queue($campaign, $this->dispatcher->audienceQuery($campaign));
        $campaign->update(['sent_at' => now()]);

        return $queued;
    }

    /**
     * Guests whose trigger event (plus the delay) has passed since activation.
     *
     * @return Builder<Guest>
     */
    public function dueForTrigger(Campaign $campaign): Builder
    {
        $delay = static::delayMinutes($campaign->trigger_delay);
        $since = $campaign->activated_at ?? $campaign->updated_at;
        $eventBefore = now()->subMinutes($delay);
        $query = $this->dispatcher->audienceQuery($campaign);

        return match ($campaign->trigger_event) {
            self::CONNECTS => $query->whereBetween('last_connected', [$since, $eventBefore]),
            // Arrival at CHECK_IN_HOUR: message 24h before, then the delay.
            self::BEFORE_ARRIVAL => $query->whereNotNull('check_in')
                ->whereDate('check_in', '<=', $this->dateOf($eventBefore->copy()->addDay(), self::CHECK_IN_HOUR))
                ->whereDate('check_in', '>=', $this->dateOf($since->copy()->addDay(), self::CHECK_IN_HOUR)),
            self::CHECKOUT_DAY => $query->whereNotNull('check_out')
                ->whereDate('check_out', '<=', $this->dateOf($eventBefore, self::CHECKOUT_MESSAGE_HOUR))
                ->whereDate('check_out', '>=', $since->toDateString()),
            default => $query->whereRaw('1 = 0'),
        };
    }

    /**
     * "Instant", "1 Hour", "6 Hours", "24 Hours", "3 Days", "7 Days" → minutes.
     */
    public static function delayMinutes(?string $delay): int
    {
        if (! preg_match('/(\d+)\s*(minute|hour|day)/i', (string) $delay, $m)) {
            return 0;
        }

        return (int) $m[1] * ['minute' => 1, 'hour' => 60, 'day' => 1440][strtolower($m[2])];
    }

    /**
     * The latest date whose event (at $hour that day) is at or before $moment.
     */
    private function dateOf(Carbon $moment, int $hour): string
    {
        return $moment->copy()->subHours($hour)->toDateString();
    }

    /**
     * Queue the campaign for guests who haven't had it yet.
     *
     * @param  Builder<Guest>  $guests
     */
    private function queue(Campaign $campaign, Builder $guests): int
    {
        $queued = 0;

        $guests->whereDoesntHave('campaignRecipients', fn ($q) => $q->where('campaign_id', $campaign->id))
            ->each(function (Guest $guest) use ($campaign, &$queued) {
                // The recipient row is the once-only guard, even if the send is retried.
                CampaignRecipient::firstOrCreate(['campaign_id' => $campaign->id, 'guest_id' => $guest->id]);
                SendCampaignJob::dispatch($campaign, $guest);
                $queued++;
            });

        return $queued;
    }
}
