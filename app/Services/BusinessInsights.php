<?php

namespace App\Services;

use App\Models\AccessPoint;
use App\Models\Guest;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The business dashboard's numbers, for a café with one site up to a
 * company with many: customers this month, how many come back, Google
 * review requests, birthday treats, and each site side by side. Scoped to
 * the sites the user can see (a manager sees only theirs). Read-only.
 */
class BusinessInsights
{
    /** How far back "when customers connect" looks. */
    public const BUSY_DAYS = 90;

    /**
     * @return array{summary: array<string, int|float|null>, sites: list<array<string, mixed>>, regions: list<array<string, mixed>>, busyDays: list<array{name: string, customers: int}>, busyHours: list<array{name: string, customers: int}>}
     */
    public function for(User $user, ?Carbon $now = null): array
    {
        $now ??= now();
        $monthStart = $now->copy()->startOfMonth();
        $ids = $user->propertyIds();
        $guests = fn () => Guest::whereIn('property_id', $ids);

        $total = $guests()->count();
        $consented = $guests()->whereNotNull('consented_at')->count();

        return [
            'summary' => [
                'customers' => $total,
                'newThisMonth' => $guests()->where('created_at', '>=', $monthStart)->count(),
                'returningThisMonth' => $guests()->where('created_at', '<', $monthStart)->where('last_connected', '>=', $monthStart)->count(),
                'visits' => (int) $guests()->sum('total_visits'),
                'repeatRate' => $total ? round($guests()->where('total_visits', '>', 1)->count() / $total * 100) : null,
                'optInRate' => $consented ? round($guests()->where('marketing_opt_in', true)->count() / $consented * 100) : null,
                'reviewRequests' => $guests()->where('review_requested_at', '>=', $monthStart)->count(),
                'reviewClicks' => $guests()->where('review_clicked_at', '>=', $monthStart)->count(),
                'birthdayTreats' => $guests()->where('birthday_sent_year', $now->year)->count(),
                'sites' => count($ids),
                'accessPointsOnline' => AccessPoint::whereIn('property_id', $ids)->where('status', 'online')->count(),
            ],
            'sites' => $sites = $this->sites($ids, $monthStart),
            'regions' => $this->regions($sites),
            ...$this->busyTimes($ids, $now),
        ];
    }

    /** Each site side by side (sortable in the table). */
    private function sites(array $ids, Carbon $monthStart): array
    {
        return Property::whereIn('id', $ids)->orderBy('name')
            ->withCount([
                'guests as customers',
                'guests as new_this_month' => fn ($q) => $q->where('created_at', '>=', $monthStart),
                'guests as returning' => fn ($q) => $q->where('total_visits', '>', 1),
                'guests as review_requests' => fn ($q) => $q->where('review_requested_at', '>=', $monthStart),
                'guests as review_clicks' => fn ($q) => $q->where('review_clicked_at', '>=', $monthStart),
                'accessPoints as access_points',
                'accessPoints as access_points_online' => fn ($q) => $q->where('status', 'online'),
            ])
            ->get()
            ->map(fn (Property $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'region' => $p->region,
                'customers' => $p->customers,
                'newThisMonth' => $p->new_this_month,
                'repeatRate' => $p->customers ? round($p->returning / $p->customers * 100) : null,
                'reviewRequests' => $p->review_requests,
                'reviewClicks' => $p->review_clicks,
                'network' => $p->access_points === 0 ? 'none' : ($p->access_points_online === $p->access_points ? 'online' : ($p->access_points_online > 0 ? 'partial' : 'offline')),
            ])
            ->all();
    }

    /** Totals per region, once the sites use more than one. */
    private function regions(array $sites): array
    {
        $byRegion = collect($sites)->groupBy(fn ($s) => $s['region'] ?: 'No region');
        if ($byRegion->count() < 2) {
            return [];
        }

        return $byRegion->map(fn (Collection $group, $region) => [
            'region' => $region,
            'sites' => $group->count(),
            'customers' => $group->sum('customers'),
            'newThisMonth' => $group->sum('newThisMonth'),
            'reviewRequests' => $group->sum('reviewRequests'),
        ])->sortByDesc('customers')->values()->all();
    }

    /**
     * When customers connect, from each customer's latest connection in the
     * last 90 days (one per customer, so regulars don't crowd the chart).
     *
     * @return array{busyDays: list<array{name: string, customers: int}>, busyHours: list<array{name: string, customers: int}>}
     */
    private function busyTimes(array $ids, Carbon $now): array
    {
        $times = Guest::whereIn('property_id', $ids)
            ->where('last_connected', '>=', $now->copy()->subDays(self::BUSY_DAYS))
            ->pluck('last_connected')
            ->map(fn ($at) => Carbon::parse($at)->setTimezone(config('app.timezone')));

        $days = collect(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'])
            ->map(fn ($day, $i) => ['name' => $day, 'customers' => $times->filter(fn ($t) => $t->dayOfWeekIso === $i + 1)->count()]);
        $hours = collect(range(0, 23))
            ->map(fn ($h) => ['name' => sprintf('%02d:00', $h), 'customers' => $times->filter(fn ($t) => $t->hour === $h)->count()]);

        return ['busyDays' => $days->all(), 'busyHours' => $hours->all()];
    }
}
