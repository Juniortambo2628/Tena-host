<?php

namespace App\Jobs;

use App\Models\Property;
use App\Services\Pms\PmsSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncPmsGuests implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Property $property,
        public array $payload = [],
    ) {
        //
    }

    public function handle(PmsSyncService $syncService): void
    {
        if (! $this->property->pms_integration_type) {
            return;
        }

        try {
            $syncService->sync(
                $this->property,
                strtolower($this->property->pms_integration_type),
                $this->payload
            );
        } catch (\Throwable $e) {
            Log::error("Scheduled PMS sync failed for property {$this->property->id}", [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
