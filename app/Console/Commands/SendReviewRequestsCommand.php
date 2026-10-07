<?php

namespace App\Console\Commands;

use App\Services\ReviewRequestService;
use Illuminate\Console\Command;

class SendReviewRequestsCommand extends Command
{
    protected $signature = 'reviews:send';

    protected $description = 'Send due thank-you and review requests to guests';

    public function handle(ReviewRequestService $reviews): int
    {
        $this->info('Review requests sent: '.$reviews->sendDue());

        return self::SUCCESS;
    }
}
