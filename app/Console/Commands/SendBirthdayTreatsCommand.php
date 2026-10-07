<?php

namespace App\Console\Commands;

use App\Models\Guest;
use App\Models\User;
use App\Services\Messaging\Messenger;
use Illuminate\Console\Command;

/**
 * Birthday treats for business customers: on the customer's birthday,
 * send the business's birthday offer, once a year. Only to customers who
 * opted in to offers.
 */
class SendBirthdayTreatsCommand extends Command
{
    protected $signature = 'birthdays:send';

    protected $description = 'Send business customers their birthday treat';

    public function handle(Messenger $messenger): int
    {
        $today = now('Africa/Nairobi');
        $sent = 0;

        Guest::with('property.host')
            ->where('birthday', $today->format('m-d'))
            ->where('marketing_opt_in', true)
            ->whereNotNull('phone')
            ->where(fn ($q) => $q->whereNull('birthday_sent_year')->orWhere('birthday_sent_year', '<', $today->year))
            ->whereHas('property', fn ($q) => $q->whereNotNull('birthday_offer')->where('birthday_offer', '!=', '')
                ->whereHas('host', fn ($q) => $q->where('account_type', User::ACCOUNT_BUSINESS)))
            ->each(function (Guest $guest) use ($messenger, $today, &$sent) {
                if ($messenger->toGuest($guest, 'whatsapp', $guest->property->birthday_offer)['success']) {
                    $guest->forceFill(['birthday_sent_year' => $today->year])->save();
                    $sent++;
                }
            });

        $this->info("Birthday treats sent: {$sent}");

        return self::SUCCESS;
    }
}
