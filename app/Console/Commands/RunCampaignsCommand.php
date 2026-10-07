<?php

namespace App\Console\Commands;

use App\Services\CampaignAutomation;
use Illuminate\Console\Command;

class RunCampaignsCommand extends Command
{
    protected $signature = 'campaigns:run';

    protected $description = 'Send scheduled broadcasts and trigger campaigns that are due';

    public function handle(CampaignAutomation $automation): int
    {
        $this->info('Campaign messages queued: '.$automation->run());

        return self::SUCCESS;
    }
}
