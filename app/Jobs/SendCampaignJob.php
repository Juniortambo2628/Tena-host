<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\Guest;
use App\Models\MarketingEvent;
use App\Services\CampaignDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Campaign $campaign,
        public Guest $guest,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(CampaignDispatcher $dispatcher): void
    {
        $sent = $dispatcher->deliver($this->campaign, $this->guest);

        if ($sent) {
            $this->campaign->increment('total_sent');
            MarketingEvent::create([
                'campaign_id' => $this->campaign->id,
                'guest_id' => $this->guest->id,
                'event_type' => 'sent',
            ]);
        }
    }
}
