<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StaffDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $propertyIds = $user->staffProperties()->pluck('properties.id');

        $properties = $user->staffProperties()
            ->withCount(['guests', 'accessPoints'])
            ->get();

        $recentGuests = Guest::whereIn('property_id', $propertyIds)
            ->latest()
            ->take(10)
            ->with('property:id,name')
            ->get();

        $pendingOrders = Order::whereIn('property_id', $propertyIds)
            ->where('status', 'pending')
            ->with(['guest:id,first_name,last_name', 'property:id,name', 'amenity:id,name'])
            ->latest()
            ->take(10)
            ->get();

        return Inertia::render('Staff/Dashboard', [
            'properties' => $properties,
            'recentGuests' => $recentGuests,
            'pendingOrders' => $pendingOrders,
            'stats' => [
                'assignedProperties' => $properties->count(),
                'totalGuests' => Guest::whereIn('property_id', $propertyIds)->count(),
                'pendingOrders' => $pendingOrders->count(),
            ],
        ]);
    }
}
