<?php

namespace App\Console\Commands;

use App\Jobs\SyncPmsGuests;
use App\Models\Property;
use Illuminate\Console\Command;

class SyncPmsGuestsCommand extends Command
{
    protected $signature = 'pms:sync-guests {property? : Optional property ID to sync}';

    protected $description = 'Sync guest reservations from configured PMS providers';

    public function handle(): int
    {
        $query = Property::query()
            ->whereNotNull('pms_integration_type')
            ->whereIn('pms_connection_status', ['connected', 'pending']);

        if ($propertyId = $this->argument('property')) {
            $query->where('id', $propertyId);
        }

        $count = $query->count();

        if ($count === 0) {
            $this->warn('No properties with PMS integration configured.');

            return self::SUCCESS;
        }

        $this->info("Queueing PMS sync for {$count} properties...");

        foreach ($query->cursor() as $property) {
            SyncPmsGuests::dispatch($property);
            $this->info("Queued property: {$property->name}");
        }

        $this->info('Done.');

        return self::SUCCESS;
    }
}
