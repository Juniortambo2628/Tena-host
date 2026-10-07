<?php

namespace App\Console\Commands;

use App\Services\MonthlyReportService;
use Illuminate\Console\Command;

class SendMonthlyReportsCommand extends Command
{
    protected $signature = 'reports:monthly {--month= : Month to report on (YYYY-MM), default last month}';

    protected $description = 'Send each account its monthly report';

    public function handle(MonthlyReportService $reports): int
    {
        $month = $this->option('month')
            ? now()->createFromFormat('Y-m-d', $this->option('month').'-01')
            : now()->subMonthNoOverflow();

        $this->info('Monthly reports sent: '.$reports->sendAll($month));

        return self::SUCCESS;
    }
}
