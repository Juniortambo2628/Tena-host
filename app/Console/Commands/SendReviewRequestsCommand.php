<?php

namespace App\Console\Commands;

use App\Models\Property;
use App\Services\ReviewRequestService;
use Illuminate\Console\Command;

class SendReviewRequestsCommand extends Command
{
    protected $signature = 'reviews:send
        {--property= : Only this property (its id)}
        {--everyone : Ask everyone the property can still ask, however long ago they came (needs --property)}
        {--dry-run : With --everyone, list who would be asked and send nothing}';

    protected $description = 'Send due thank-you and review requests to guests';

    public function handle(ReviewRequestService $reviews): int
    {
        if (! $this->option('everyone')) {
            $this->info('Review requests sent: '.$reviews->sendDue());

            return self::SUCCESS;
        }

        $property = Property::find($this->option('property'));
        if (! $property) {
            $this->error('--everyone needs --property=<id> of an existing property.');

            return self::FAILURE;
        }
        if (! $property->review_url) {
            $this->error("{$property->name} has no review link yet: add it on the property page first.");

            return self::FAILURE;
        }

        $guests = $reviews->everyone($property)->get()->each(fn ($g) => $g->setRelation('property', $property));
        $this->line("{$property->name}: ".$guests->count().' to ask, channel '.ReviewRequestService::channel($property).'.');
        foreach ($guests as $guest) {
            $this->line("  {$guest->first_name} by ".$reviews->channelFor($guest));
        }

        if ($this->option('dry-run')) {
            $this->warn('Dry run: nothing was sent.');

            return self::SUCCESS;
        }

        $sent = $guests->filter(fn ($guest) => $reviews->send($guest))->count();
        $this->info("Review requests sent: {$sent}");

        return self::SUCCESS;
    }
}
