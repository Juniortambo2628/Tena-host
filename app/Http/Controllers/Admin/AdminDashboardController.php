<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use App\Models\MpesaTransaction;
use App\Models\Property;
use App\Models\Registration;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
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
                'totalRevenue' => MpesaTransaction::where('Status', 'completed')->sum('Amount'),
                'completedTransactions' => MpesaTransaction::where('Status', 'completed')->count(),
                'totalSignups' => Registration::count(),
            ],
            'hosts' => $hosts,
            'recentRegistrations' => $recentRegistrations,
            'analytics' => $this->getAnalytics(),
        ]);
    }

    private function getAnalytics(): array
    {
        // Revenue over last 6 months (real M-Pesa data)
        $revenue = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $revenue[] = [
                'name' => $date->format('M'),
                'revenue' => (float) MpesaTransaction::where('Status', 'completed')
                    ->whereMonth('created_at', $date->month)
                    ->whereYear('created_at', $date->year)
                    ->sum('Amount'),
            ];
        }

        // Guest growth over last 6 months
        $guests = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $guests[] = [
                'name' => $date->format('M'),
                'guests' => Guest::whereMonth('created_at', $date->month)
                    ->whereYear('created_at', $date->year)
                    ->count(),
            ];
        }

        // Property growth over last 6 months
        $properties = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $properties[] = [
                'name' => $date->format('M'),
                'properties' => Property::whereMonth('created_at', $date->month)
                    ->whereYear('created_at', $date->year)
                    ->count(),
            ];
        }

        // Registration signups over last 6 months
        $signups = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $signups[] = [
                'name' => $date->format('M'),
                'signups' => Registration::whereMonth('created_at', $date->month)
                    ->whereYear('created_at', $date->year)
                    ->count(),
            ];
        }

        // Registration referral sources
        $referralSources = Registration::select('referral_source', DB::raw('count(*) as count'))
            ->whereNotNull('referral_source')
            ->where('referral_source', '!=', '')
            ->groupBy('referral_source')
            ->orderByDesc('count')
            ->limit(6)
            ->get()
            ->map(fn ($r) => ['name' => $r->referral_source, 'value' => $r->count])
            ->toArray();

        // Property type breakdown
        $propertyTypes = Registration::select('property_type', DB::raw('count(*) as count'))
            ->whereNotNull('property_type')
            ->where('property_type', '!=', '')
            ->groupBy('property_type')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($r) => ['name' => $r->property_type, 'value' => $r->count])
            ->toArray();

        // Daily guest connections (last 14 days)
        $dailyGuests = [];
        for ($i = 13; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dailyGuests[] = [
                'name' => $date->format('M d'),
                'guests' => Guest::whereDate('created_at', $date)->count(),
            ];
        }

        // Transaction status breakdown
        $transactionStatus = MpesaTransaction::select('Status', DB::raw('count(*) as count'))
            ->groupBy('Status')
            ->get()
            ->map(fn ($r) => ['name' => $r->Status, 'value' => $r->count])
            ->toArray();

        return [
            'revenue' => $revenue,
            'guests' => $guests,
            'properties' => $properties,
            'signups' => $signups,
            'referralSources' => $referralSources,
            'propertyTypes' => $propertyTypes,
            'dailyGuests' => $dailyGuests,
            'transactionStatus' => $transactionStatus,
        ];
    }
}
