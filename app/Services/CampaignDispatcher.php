<?php

namespace App\Services;

use App\Mail\CampaignEmail;
use App\Models\Campaign;
use App\Models\Guest;
use App\Services\Messaging\Messenger;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CampaignDispatcher
{
    public function __construct(protected Messenger $messenger) {}

    /**
     * Deliver a campaign to a single guest.
     */
    public function deliver(Campaign $campaign, Guest $guest): bool
    {
        try {
            if ($campaign->type === 'email') {
                if (! $guest->email) {
                    Log::warning('Campaign email skipped: guest has no email', [
                        'campaign_id' => $campaign->id,
                        'guest_id' => $guest->id,
                    ]);

                    return false;
                }

                Mail::to($guest->email)->send(new CampaignEmail($campaign, $guest));

                return true;
            }

            if (in_array($campaign->type, Messenger::CHANNELS, true)) {
                $result = $this->messenger->toGuest($guest, $campaign->type, $campaign->content ?? '');

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
     * Resolve the audience for a campaign.
     *
     * @return Collection<int, Guest>
     */
    public function audience(Campaign $campaign)
    {
        $query = Guest::query();

        if ($campaign->audience_property_id) {
            $query->where('property_id', $campaign->audience_property_id);
        } elseif ($campaign->property_id) {
            $query->where('property_id', $campaign->property_id);
        }

        // Campaigns are direct marketing: guests who gave contact consent at
        // WiFi login but didn't opt in to offers are left out (Kenya Data Protection Act, 2019).
        $query->where(fn ($q) => $q->whereNull('consented_at')->orWhere('marketing_opt_in', true));

        if ($campaign->audience_from) {
            $query->whereDate('created_at', '>=', $campaign->audience_from);
        }

        if ($campaign->audience_to) {
            $query->whereDate('created_at', '<=', $campaign->audience_to);
        }

        return $query->get();
    }
}
