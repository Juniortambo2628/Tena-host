<?php

namespace App\Services;

use App\Mail\MonthlyReportMail;
use App\Models\Guest;
use App\Models\MarketingEvent;
use App\Models\User;
use App\Services\Billing\PlanPricing;
use App\Services\Messaging\Messenger;
use App\Support\Brand;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * The monthly report (reports:monthly, 1st of the month): what TenaFi did
 * for each account last month. Emailed, with a short WhatsApp summary.
 * Included on Starter and Growth; everyone gets it while billing is off.
 */
class MonthlyReportService
{
    public const PLANS = ['starter', 'growth'];

    public function __construct(protected Messenger $messenger) {}

    /**
     * @return int reports sent
     */
    public function sendAll(Carbon $month): int
    {
        $sent = 0;

        $this->recipients()->each(function (User $user) use ($month, &$sent) {
            $sent += (int) $this->send($user, $month);
        });

        return $sent;
    }

    public function recipients()
    {
        return User::where('role', 'host')
            ->whereHas('properties')
            ->when(PlanPricing::enforced(), fn ($q) => $q
                ->whereIn('billing_plan', self::PLANS)
                ->whereHas('subscriptions', fn ($q) => $q->where('type', 'default')->where('ends_at', '>', now())))
            ->get();
    }

    /**
     * @return array{month: string, places: int, new_guests: int, returning_guests: int, messages_sent: int, reviews_requested: int, reviews_opened: int}
     */
    public function build(User $user, Carbon $month): array
    {
        $from = $month->copy()->startOfMonth();
        $to = $month->copy()->endOfMonth();
        $propertyIds = $user->properties()->pluck('id');
        $guests = fn () => Guest::whereIn('property_id', $propertyIds);

        return [
            'month' => $from->format('F Y'),
            'places' => $propertyIds->count(),
            'new_guests' => $guests()->whereBetween('created_at', [$from, $to])->count(),
            'returning_guests' => $guests()->where('created_at', '<', $from)->whereBetween('last_connected', [$from, $to])->count(),
            'messages_sent' => MarketingEvent::where('event_type', 'sent')->whereBetween('created_at', [$from, $to])
                ->whereHas('campaign', fn ($q) => $q->where('user_id', $user->id))->count(),
            'reviews_requested' => $guests()->whereBetween('review_requested_at', [$from, $to])->count(),
            'reviews_opened' => $guests()->whereBetween('review_clicked_at', [$from, $to])->count(),
        ];
    }

    public function send(User $user, Carbon $month): bool
    {
        $report = $this->build($user, $month);
        $sent = false;

        if ($user->email) {
            try {
                Mail::to($user->email)->send(new MonthlyReportMail($user, $report));
                $sent = true;
            } catch (\Throwable $e) {
                Log::error('Monthly report email failed: '.$e->getMessage(), ['user' => $user->id]);
            }
        }

        if ($user->phone_number) {
            $sent = $this->messenger->send('whatsapp', $user->phone_number, $this->summary($user, $report))['success'] || $sent;
        }

        return $sent;
    }

    /**
     * @param  array<string, mixed>  $report
     */
    public function summary(User $user, array $report): string
    {
        $people = $user->isBusiness() ? 'customers' : 'guests';

        return Brand::name()." report for {$report['month']}: {$report['new_guests']} new {$people}, {$report['returning_guests']} returning, "
            ."{$report['messages_sent']} campaign messages sent, {$report['reviews_requested']} review requests ({$report['reviews_opened']} opened). "
            .'Details: '.route('dashboard');
    }
}
