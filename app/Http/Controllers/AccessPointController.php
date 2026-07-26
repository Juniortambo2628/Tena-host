<?php

namespace App\Http\Controllers;

use App\Models\AccessPoint;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AccessPointController extends Controller
{
    /**
     * Display a listing of access points for the host's properties.
     */
    public function index(Request $request)
    {
        $accessPoints = AccessPoint::forHost(Auth::user())
            ->with('property:id,name')
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('mac_address', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(15);

        return Inertia::render('Host/AccessPoints/Index', [
            'accessPoints' => $accessPoints,
            'filters' => $request->only(['search']),
            'properties' => Auth::user()->properties,
        ]);
    }

    /**
     * Store a newly created access point.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'property_id' => 'required|exists:properties,id',
            'name' => 'required|string|max:255',
            'mac_address' => 'required|string|max:255|unique:access_points,mac_address',
            'status' => 'nullable|in:online,offline',
        ]);

        $property = Property::findOrFail($validated['property_id']);
        $this->authorize('update', $property);

        AccessPoint::create($validated);

        return redirect()->back()->with('success', 'Access point added successfully.');
    }

    /**
     * Display the specified access point.
     */
    public function show(AccessPoint $accessPoint)
    {
        $this->authorize('view', $accessPoint);

        return Inertia::render('Host/AccessPoints/Show', [
            'accessPoint' => $accessPoint->load('property:id,name,address'),
        ]);
    }

    /**
     * Update the specified access point.
     */
    public function update(Request $request, AccessPoint $accessPoint)
    {
        $this->authorize('update', $accessPoint);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'mac_address' => 'sometimes|string|max:255|unique:access_points,mac_address,'.$accessPoint->id,
            'status' => 'sometimes|in:online,offline',
            'last_seen' => 'nullable|date',
            'connected_clients_count' => 'sometimes|integer|min:0',
        ]);

        $accessPoint->update($validated);

        return redirect()->back()->with('success', 'Access point updated successfully.');
    }

    /**
     * Remove the specified access point.
     */
    public function destroy(AccessPoint $accessPoint)
    {
        $this->authorize('delete', $accessPoint);
        $accessPoint->delete();

        return redirect()->back()->with('success', 'Access point deleted successfully.');
    }
}
