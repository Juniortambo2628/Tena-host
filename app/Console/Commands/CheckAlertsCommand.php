<?php

namespace App\Console\Commands;

use App\Services\PropertyMonitorService;
use Illuminate\Console\Command;

class CheckAlertsCommand extends Command
{
    protected $signature = 'alerts:check';

    protected $description = 'Send outage and occupancy alerts to hosts';

    public function handle(PropertyMonitorService $monitor): int
    {
        $result = $monitor->check();
        $this->info(collect($result)->map(fn ($n, $k) => "{$k}: {$n}")->implode(', '));

        return self::SUCCESS;
    }
}
