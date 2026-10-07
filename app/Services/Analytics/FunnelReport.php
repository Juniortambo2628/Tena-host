<?php

namespace App\Services\Analytics;

use App\Models\Analytics;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * The public-site funnel per audience, from the first click to a paying
 * account: tracked page events (TrackController) joined with what the
 * database knows (sign-ups saved, accounts created, active plans).
 */
class FunnelReport
{
    /** Audience (sign-up type) => public page slug. */
    public const AUDIENCES = [
        Registration::TYPE_HOST => 'hosts',
        Registration::TYPE_BUSINESS => 'business',
    ];

    /**
     * @return array<string, list<array{name: string, value: int}>> one funnel per audience
     */
    public function funnels(int $days = 30): array
    {
        $since = now()->subDays($days)->startOfDay();

        return collect(self::AUDIENCES)->map(fn (string $slug, string $type) => [
            ['name' => 'Path card clicks', 'value' => $this->events("path_card_{$slug}", null, $since)],
            ['name' => 'Join clicks', 'value' => $this->events('join_click', $slug, $since)],
            ['name' => 'Step 1 done', 'value' => $this->events('signup_step_1', $slug, $since)],
            ['name' => 'Step 2 done', 'value' => $this->events('signup_step_2', $slug, $since)],
            ['name' => 'Signed up', 'value' => Registration::where('type', $type)->where('created_at', '>=', $since)->count()],
            ['name' => 'Account created', 'value' => Registration::where('type', $type)->where('created_at', '>=', $since)->whereNotNull('user_id')->count()],
            ['name' => 'Paying', 'value' => $this->paying($type, $since)],
        ])->all();
    }

    /**
     * Sign-ups per month, split by audience.
     *
     * @return list<array{name: string, host: int, business: int}>
     */
    public function signupsByType(int $months = 6): array
    {
        return collect(range($months - 1, 0))->map(function (int $ago) {
            $month = now()->startOfMonth()->subMonths($ago);
            $counts = Registration::whereBetween('created_at', [$month, $month->copy()->endOfMonth()])
                ->selectRaw('type, count(*) as total')->groupBy('type')->pluck('total', 'type');

            return [
                'name' => $month->format('M'),
                'host' => (int) ($counts[Registration::TYPE_HOST] ?? 0),
                'business' => (int) ($counts[Registration::TYPE_BUSINESS] ?? 0),
            ];
        })->all();
    }

    /**
     * Active paid subscriptions by plan.
     *
     * @return list<array{name: string, value: int}>
     */
    public function planMix(): array
    {
        return User::whereNotNull('billing_plan')
            ->whereHas('subscriptions', fn ($q) => $q->where('type', 'default')->where('ends_at', '>', now()))
            ->selectRaw('billing_plan, count(*) as total')->groupBy('billing_plan')->pluck('total', 'billing_plan')
            ->map(fn ($total, $plan) => ['name' => config("billing.plans.{$plan}.name", ucfirst($plan)), 'value' => (int) $total])
            ->values()->all();
    }

    /**
     * Sum a tracked event since a date. Without a page, every page counts.
     */
    protected function events(string $event, ?string $page, Carbon $since): int
    {
        $metric = 'public.'.$event;

        return (int) Analytics::whereDate('date_recorded', '>=', $since)
            ->when($page,
                fn ($q) => $q->where('metric_name', "{$metric}:{$page}"),
                fn ($q) => $q->where(fn ($q) => $q->where('metric_name', $metric)->orWhere('metric_name', 'like', "{$metric}:%")))
            ->sum('metric_value');
    }

    protected function paying(string $type, Carbon $since): int
    {
        return User::where('account_type', $type === Registration::TYPE_BUSINESS ? User::ACCOUNT_BUSINESS : User::ACCOUNT_HOST)
            ->whereIn('id', Registration::where('type', $type)->where('created_at', '>=', $since)->whereNotNull('user_id')->select('user_id'))
            ->whereHas('subscriptions', fn ($q) => $q->where('type', 'default')->where('ends_at', '>', now()))
            ->count();
    }
}
