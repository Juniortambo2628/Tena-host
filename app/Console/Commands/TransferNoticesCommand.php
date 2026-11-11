<?php

namespace App\Console\Commands;

use App\Models\PropertyTransfer;
use App\Services\TransferNoticeService;
use Illuminate\Console\Command;

/** Sends the next batch of "new owner" notes for completed property transfers (scheduled every five minutes). */
class TransferNoticesCommand extends Command
{
    protected $signature = 'transfers:notices';

    protected $description = 'Tell the guests of transferred properties who runs them now, in batches';

    public function handle(TransferNoticeService $notices): int
    {
        $sent = 0;
        PropertyTransfer::where('status', 'completed')->where('notice_status', 'pending')->orderBy('id')
            ->each(function (PropertyTransfer $transfer) use ($notices, &$sent) {
                $sent += $notices->sendBatch($transfer);
            });

        $this->info("Transfer notices sent: {$sent}");

        return self::SUCCESS;
    }
}
