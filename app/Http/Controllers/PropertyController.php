<?php

namespace App\Http\Controllers;

use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class PropertyController extends Controller
{
    /**
     * Display a listing of the properties.
     */
    public function index()
    {
        $properties = Auth::user()->properties()->withCount(['guests', 'accessPoints'])->get();

        return Inertia::render('Host/Properties/Index', [
            'properties' => $properties,
        ]);
    }

    /**
     * Display the specified property.
     */
    public function show(Property $property)
    {
        $this->authorize('view', $property);

        return Inertia::render('Host/Properties/Show', [
            'property' => $property->loadCount(['guests', 'accessPoints']),
        ]);
    }

    /**
     * Show the form for editing the specified property.
     */
    public function edit(Property $property)
    {
        $this->authorize('update', $property);

        return Inertia::render('Host/Properties/Edit', [
            'property' => $property,
        ]);
    }

    /**
     * Store a newly created property.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'wifi_ssid' => 'nullable|string|max:255',
            'occupancy_threshold' => 'integer|min:1',
            'branding_json' => 'nullable|array',
            'splash_image' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('splash_image')) {
            $path = $request->file('splash_image')->store('properties', 'public');
            $validated['splash_image_path'] = '/storage/'.$path;
        }

        $property = Auth::user()->properties()->create($validated);

        return redirect()->back()->with('success', 'Property created successfully.');
    }

    /**
     * Update the specified property.
     */
    public function update(Request $request, Property $property)
    {
        $this->authorize('update', $property);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'wifi_ssid' => 'nullable|string|max:255',
            'occupancy_threshold' => 'integer|min:1',
            'branding_json' => 'nullable|array',
            'splash_image' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('splash_image')) {
            // Delete old image if exists
            if ($property->splash_image_path) {
                $oldPath = str_replace('/storage/', '', $property->splash_image_path);
                Storage::disk('public')->delete($oldPath);
            }

            $path = $request->file('splash_image')->store('properties', 'public');
            $validated['splash_image_path'] = '/storage/'.$path;
        }

        $property->update($validated);

        return redirect()->back()->with('success', 'Property updated successfully.');
    }

    /**
     * Remove the specified property.
     */
    public function destroy(Property $property)
    {
        $this->authorize('delete', $property);
        $property->delete();

        return redirect()->back()->with('success', 'Property deleted successfully.');
    }
}
