<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Guest;
use App\Models\Order;
use App\Models\Property;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class HostDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $propertyIds = $user->propertyIds();

        $properties = $user->properties()->withCount(['guests', 'accessPoints'])->get();

        $properties = $properties->map(function ($property) {
            $onlineAps = $property->accessPoints()->where('status', 'online')->count();
            $totalAps = $property->accessPoints_count;
            $property->network_status = $totalAps > 0 && $onlineAps === $totalAps ? 'Online' : ($onlineAps > 0 ? 'Partial' : 'Offline');
            $property->occupancy_rate = $property->guests_count > 0
                ? min(100, round(($property->guests_count / max($property->occupancy_threshold, 1)) * 100))
                : 0;

            return $property;
        });

        $totalGuests = Guest::forHost($user)->count();
        $onlineAPs = $properties->sum('access_points_count');
        $avgOccupancy = $properties->count() > 0 ? round($properties->avg('occupancy_rate')) : 0;

        $totalOrders = Order::forHost($user)->count();
        $totalRevenue = Order::forHost($user)->where('status', 'completed')->sum('total');
        $activeCampaigns = Campaign::where('user_id', $user->id)->where('status', 'Active')->count();

        // Guest trend (compare this week vs last week)
        $thisWeekGuests = Guest::forHost($user)
            ->where('created_at', '>=', Carbon::now()->startOfWeek())
            ->count();
        $lastWeekGuests = Guest::forHost($user)
            ->where('created_at', '>=', Carbon::now()->subWeek()->startOfWeek())
            ->where('created_at', '<', Carbon::now()->startOfWeek())
            ->count();
        $guestTrend = $lastWeekGuests > 0
            ? round((($thisWeekGuests - $lastWeekGuests) / $lastWeekGuests) * 100)
            : ($thisWeekGuests > 0 ? 100 : 0);

        return Inertia::render('Host/Dashboard', [
            'properties' => $properties,
            'stats' => [
                'totalGuests' => $totalGuests,
                'onlineAPs' => $onlineAPs,
                'userName' => $user->first_name ?: $user->username,
                'avgOccupancy' => $avgOccupancy.'%',
                'totalProperties' => $properties->count(),
                'totalOrders' => $totalOrders,
                'totalRevenue' => (float) $totalRevenue,
                'activeCampaigns' => $activeCampaigns,
                'guestTrend' => $guestTrend,
            ],
            'analytics' => $this->getAnalytics($user, $propertyIds),
        ]);
    }

    private function getAnalytics($user, array $propertyIds): array
    {
        // Guest growth over last 30 days
        $guestGrowth = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $guestGrowth[] = [
                'name' => $date->format('M d'),
                'guests' => Guest::forHost($user)
                    ->whereDate('created_at', $date)
                    ->count(),
            ];
        }

        // Guest growth by month (last 6 months)
        $guestMonthly = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $guestMonthly[] = [
                'name' => $date->format('M'),
                'guests' => Guest::forHost($user)
                    ->whereMonth('created_at', $date->month)
                    ->whereYear('created_at', $date->year)
                    ->count(),
            ];
        }

        // Guests per property
        $guestsPerProperty = $propertyIds
            ? Property::whereIn('id', $propertyIds)
                ->withCount('guests')
                ->get()
                ->map(fn ($p) => ['name' => $p->name, 'guests' => $p->guests_count])
                ->toArray()
            : [];

        // Occupancy rates by property
        $occupancyData = $propertyIds
            ? Property::whereIn('id', $propertyIds)
                ->get()
                ->map(fn ($p) => [
                    'name' => $p->name,
                    'occupancy' => $p->guests_count > 0
                        ? min(100, round(($p->guests_count / max($p->occupancy_threshold, 1)) * 100))
                        : 0,
                ])
                ->toArray()
            : [];

        // Order revenue over last 30 days
        $orderRevenue = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $orderRevenue[] = [
                'name' => $date->format('M d'),
                'revenue' => (float) Order::forHost($user)
                    ->whereDate('created_at', $date)
                    ->where('status', 'completed')
                    ->sum('total'),
            ];
        }

        // Guest source breakdown
        $guestSources = Guest::forHost($user)
            ->whereNotNull('source')
            ->where('source', '!=', '')
            ->select('source', DB::raw('count(*) as count'))
            ->groupBy('source')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($g) => ['name' => $g->source, 'value' => $g->count])
            ->toArray();

        // Campaign performance
        $campaignStats = Campaign::where('user_id', $user->id)
            ->select(
                'type',
                DB::raw('count(*) as count'),
                DB::raw('sum(total_sent) as total_sent'),
                DB::raw('sum(total_opened) as total_opened'),
            )
            ->groupBy('type')
            ->get()
            ->map(fn ($c) => [
                'type' => $c->type,
                'count' => $c->count,
                'sent' => $c->total_sent ?? 0,
                'opened' => $c->total_opened ?? 0,
            ])
            ->toArray();

        return [
            'guestGrowth' => $guestGrowth,
            'guestMonthly' => $guestMonthly,
            'guestsPerProperty' => $guestsPerProperty,
            'occupancyData' => $occupancyData,
            'orderRevenue' => $orderRevenue,
            'guestSources' => $guestSources,
            'campaignStats' => $campaignStats,
        ];
    }
}
