<?php

namespace App\Services;

use App\Mail\CampaignEmail;
use App\Models\Campaign;
use App\Models\Guest;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CampaignDispatcher
{
    protected SmsDriverInterface $smsDriver;

    public function __construct()
    {
        $driverClass = config('services.sms.driver');

        $this->smsDriver = $driverClass && class_exists($driverClass)
            ? app($driverClass)
            : new NullSmsDriver;
    }

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

            if ($campaign->type === 'sms') {
                if (! $guest->phone) {
                    Log::warning('Campaign SMS skipped: guest has no phone', [
                        'campaign_id' => $campaign->id,
                        'guest_id' => $guest->id,
                    ]);

                    return false;
                }

                $message = $this->personalizeContent($campaign->content ?? '', $guest);
                $result = $this->smsDriver->send($guest->phone, $message);

                if (! $result['success']) {
                    Log::error('Campaign SMS failed', [
                        'campaign_id' => $campaign->id,
                        'guest_id' => $guest->id,
                        'error' => $result['message'] ?? 'Unknown error',
                    ]);

                    return false;
                }

                return true;
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

        if ($campaign->audience_from) {
            $query->whereDate('created_at', '>=', $campaign->audience_from);
        }

        if ($campaign->audience_to) {
            $query->whereDate('created_at', '<=', $campaign->audience_to);
        }

        return $query->get();
    }

    /**
     * Personalize campaign content for a guest.
     */
    protected function personalizeContent(string $content, Guest $guest): string
    {
        $replacements = [
            '%FIRSTNAME%' => $guest->first_name,
            '%LASTNAME%' => $guest->last_name,
            '%EMAIL%' => $guest->email,
            '%PROPERTY%' => $guest->property?->name ?? '',
        ];

        return strtr($content, $replacements);
    }
}
