<?php

namespace App\Services;

use App\Mail\CampaignEmail;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Guest;
use App\Models\Property;
use App\Services\Messaging\Messenger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CampaignDispatcher
{
    /** Visits that make a guest "VIP" for targeting. */
    public const VIP_VISITS = 5;

    public function __construct(protected Messenger $messenger) {}

    /**
     * Deliver a campaign to a single guest, with tracked links.
     */
    public function deliver(Campaign $campaign, Guest $guest): bool
    {
        $recipient = CampaignRecipient::firstOrCreate(['campaign_id' => $campaign->id, 'guest_id' => $guest->id]);

        try {
            if ($campaign->type === 'email') {
                if (! $guest->email) {
                    Log::warning('Campaign email skipped: guest has no email', [
                        'campaign_id' => $campaign->id,
                        'guest_id' => $guest->id,
                    ]);

                    return false;
                }

                Mail::to($guest->email)->send(new CampaignEmail($campaign, $guest, $recipient));

                return true;
            }

            if (in_array($campaign->type, Messenger::CHANNELS, true)) {
                $content = CampaignLinks::track($campaign->content ?? '', $recipient);
                $result = $this->messenger->toGuest($guest, $campaign->type, $content);

                if (! $result['success']) {
                    Log::warning('Campaign message not sent', [
                        'campaign_id' => $campaign->id,
                        'guest_id' => $guest->id,
                        'channel' => $result['channel'],
                        'error' => $result['message'] ?? 'Unknown error',
                    ]);
                }

                return $result['success'];
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Campaign delivery failed', [
                'campaign_id' => $campaign->id,
                'guest_id' => $guest->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * The guests a campaign may reach.
     *
     * @return Collection<int, Guest>
     */
    public function audience(Campaign $campaign)
    {
        return $this->audienceQuery($campaign)->get();
    }

    /**
     * Who a campaign targets: only the owner's own guests, at the chosen
     * property, matching the audience segment and sign-up dates, and only
     * those who may receive marketing.
     *
     * @return Builder<Guest>
     */
    public function audienceQuery(Campaign $campaign): Builder
    {
        $propertyId = $campaign->audience_property_id ?: $campaign->property_id;
        $ownProperties = Property::where('user_id', $campaign->user_id)->select('id');

        return Guest::query()
            ->whereIn('property_id', $ownProperties)
            ->when($propertyId, fn ($q) => $q->where('property_id', $propertyId))
            // Campaigns are direct marketing: guests who gave contact consent
            // at WiFi login but didn't opt in to offers are left out (Kenya
            // Data Protection Act, 2019).
            ->where(fn ($q) => $q->whereNull('consented_at')->orWhere('marketing_opt_in', true))
            ->when($campaign->type === 'email', fn ($q) => $q->whereNotNull('email'))
            ->when(in_array($campaign->type, Messenger::CHANNELS, true), fn ($q) => $q->whereNotNull('phone'))
            ->tap(fn ($q) => match ($campaign->target_audience) {
                'new_guests' => $q->where('total_visits', '<=', 1),
                'returning_guests' => $q->where('total_visits', '>', 1),
                'vip_guests' => $q->where('total_visits', '>=', self::VIP_VISITS),
                default => $q,
            })
            ->when($campaign->audience_from, fn ($q) => $q->whereDate('created_at', '>=', $campaign->audience_from))
            ->when($campaign->audience_to, fn ($q) => $q->whereDate('created_at', '<=', $campaign->audience_to));
    }
}
