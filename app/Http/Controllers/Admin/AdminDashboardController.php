<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use App\Models\Property;
use App\Models\Registration;
use App\Models\User;
use Carbon\Carbon;
use Inertia\Inertia;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $totalHosts = User::where('role', 'host')->count();
        $totalProperties = Property::count();
        $totalGuests = Guest::count();
        $pendingApprovals = Registration::where('status', 'active')->count();

        $hosts = User::where('role', 'host')
            ->withCount('properties')
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($host) {
                return [
                    'id' => $host->id,
                    'name' => $host->first_name.' '.$host->last_name,
                    'email' => $host->email,
                    'properties_count' => $host->properties_count,
                    'status' => $host->email_verified_at ? 'active' : 'pending',
                    'joined' => $host->created_at->diffForHumans(),
                ];
            });

        $revenueChartData = $this->getRevenueChartData();

        $newHostsThisMonth = User::where('role', 'host')
            ->whereMonth('created_at', Carbon::now()->month)
            ->count();
        $newPropertiesThisMonth = Property::whereMonth('created_at', Carbon::now()->month)->count();

        $recentRegistrations = Registration::latest()->take(5)->get();

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'totalHosts' => $totalHosts,
                'totalProperties' => $totalProperties,
                'totalGuests' => $totalGuests,
                'pendingApprovals' => $pendingApprovals,
                'newHostsThisMonth' => $newHostsThisMonth,
                'newPropertiesThisMonth' => $newPropertiesThisMonth,
            ],
            'hosts' => $hosts,
            'revenueChartData' => $revenueChartData,
            'recentRegistrations' => $recentRegistrations,
        ]);
    }

    private function getRevenueChartData()
    {
        $data = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $hostCount = User::where('role', 'host')
                ->where('created_at', '<=', $date->copy()->endOfMonth())
                ->count();
            $monthlyRevenue = $hostCount * 49.99;

            $data[] = [
                'month' => $date->format('M'),
                'revenue' => round($monthlyRevenue, 2),
            ];
        }

        return $data;
    }
}
