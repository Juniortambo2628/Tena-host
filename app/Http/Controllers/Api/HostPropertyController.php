<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PropertyResource;
use App\Models\Property;
use Illuminate\Http\Request;

class HostPropertyController extends Controller
{
    public function index(Request $request)
    {
        $properties = $request->user()->properties()
            ->withCount(['guests', 'accessPoints'])
            ->paginate(20);

        return PropertyResource::collection($properties);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'wifi_ssid' => 'nullable|string|max:255',
            'occupancy_threshold' => 'nullable|integer|min:1',
        ]);

        $property = $request->user()->properties()->create($validated);

        return new PropertyResource($property);
    }

    public function show(Request $request, Property $property)
    {
        if ($property->user_id !== $request->user()->id) {
            abort(403);
        }

        return new PropertyResource($property->load(['accessPoints', 'amenities']));
    }

    public function update(Request $request, Property $property)
    {
        if ($property->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'address' => 'sometimes|string|max:500',
            'wifi_ssid' => 'nullable|string|max:255',
            'occupancy_threshold' => 'nullable|integer|min:1',
            'pms_integration_type' => 'nullable|in:Beds24,Cloudbeds,Hostaway',
            'pms_connection_status' => 'nullable|in:connected,disconnected,pending',
        ]);

        $property->update($validated);

        return new PropertyResource($property);
    }

    public function destroy(Request $request, Property $property)
    {
        if ($property->user_id !== $request->user()->id) {
            abort(403);
        }

        $property->delete();

        return response()->json(['message' => 'Property deleted.']);
    }
}
