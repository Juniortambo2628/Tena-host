<?php

namespace App\Console\Commands;

use App\Models\PropertyTransfer;
use App\Services\PropertyTransferService;
use Illuminate\Console\Command;

/** Hands over accepted property transfers whose 48-hour cooling-off has passed (scheduled every five minutes). */
class CompleteTransfersCommand extends Command
{
    protected $signature = 'transfers:complete';

    protected $description = 'Complete accepted property transfers once their cooling-off has passed';

    public function handle(PropertyTransferService $transfers): int
    {
        $done = 0;
        PropertyTransfer::where('status', 'accepted')->whereNull('held_at')->where('completes_at', '<=', now())->orderBy('id')
            ->each(function (PropertyTransfer $transfer) use ($transfers, &$done) {
                if ($transfers->complete($transfer) !== null) {
                    $done++;
                }
            });

        $this->info("Transfers completed: {$done}");

        return self::SUCCESS;
    }
}
