<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use App\Models\MpesaTransaction;
use App\Models\Property;
use App\Models\Registration;
use App\Models\User;
use App\Services\Analytics\FunnelReport;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AdminDashboardController extends Controller
{
    public function __construct(protected FunnelReport $funnel) {}

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
        $revenue = $this->monthly('revenue', fn ($q) => (float) $q(MpesaTransaction::where('Status', 'completed'))->sum('Amount'));

        // Guest growth over last 6 months
        $guests = $this->monthly('guests', fn ($q) => $q(Guest::query())->count());

        // Property growth over last 6 months
        $properties = $this->monthly('properties', fn ($q) => $q(Property::query())->count());

        // Registration signups over last 6 months
        $signups = $this->monthly('signups', fn ($q) => $q(Registration::query())->count());

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
            'signupsByType' => $this->funnel->signupsByType(),
            'funnels' => $this->funnel->funnels(),
            'planMix' => $this->funnel->planMix(),
        ];
    }

    /**
     * One point per month for the last six months. $count receives a
     * scope that limits a query to that month's created_at.
     *
     * @return list<array<string, mixed>>
     */
    private function monthly(string $key, callable $count): array
    {
        return collect(range(5, 0))->map(function (int $ago) use ($key, $count) {
            $month = Carbon::now()->startOfMonth()->subMonths($ago);
            $inMonth = fn ($query) => $query->whereBetween('created_at', [$month, $month->copy()->endOfMonth()]);

            return ['name' => $month->format('M'), $key => $count($inMonth)];
        })->all();
    }
}
