<?php

namespace App\Http\Controllers;

use App\Models\Guest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
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

        $guestChartData = $this->getGuestChartData($propertyIds);

        $totalGuests = Guest::forHost($user)->count();
        $onlineAPs = $properties->sum('access_points_count');
        $avgOccupancy = $properties->count() > 0 ? round($properties->avg('occupancy_rate')) : 0;

        return Inertia::render('Host/Dashboard', [
            'properties' => $properties,
            'stats' => [
                'totalGuests' => $totalGuests,
                'onlineAPs' => $onlineAPs,
                'userName' => $user->first_name ?: $user->username,
                'avgOccupancy' => $avgOccupancy.'%',
                'totalProperties' => $properties->count(),
            ],
            'guestChartData' => $guestChartData,
        ]);
    }

    private function getGuestChartData($propertyIds)
    {
        $days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $count = Guest::forHost(Auth::user())
                ->whereDate('created_at', $date->toDateString())
                ->count();

            $days[] = [
                'name' => $date->format('D'),
                'guests' => $count,
            ];
        }

        return $days;
    }
}
