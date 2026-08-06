<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Traits\HasImageUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PropertyController extends Controller
{
    use HasImageUpload;

    public function index()
    {
        $properties = Auth::user()->properties()->withCount(['guests', 'accessPoints'])->get();

        return Inertia::render('Host/Properties/Index', [
            'properties' => $properties,
        ]);
    }

    public function show(Property $property)
    {
        $this->authorize('view', $property);

        return Inertia::render('Host/Properties/Show', [
            'property' => $property->loadCount(['guests', 'accessPoints']),
        ]);
    }

    public function edit(Property $property)
    {
        $this->authorize('update', $property);

        return Inertia::render('Host/Properties/Edit', [
            'property' => $property,
        ]);
    }

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
            $validated['splash_image_path'] = $this->storeImage($request->file('splash_image'), 'properties');
        }

        Auth::user()->properties()->create($validated);

        return redirect()->back()->with('success', 'Property created successfully.');
    }

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
            $validated['splash_image_path'] = $this->updateImage(
                $request->file('splash_image'),
                $property->splash_image_path,
                'properties'
            );
        }

        $property->update($validated);

        return redirect()->back()->with('success', 'Property updated successfully.');
    }

    public function destroy(Property $property)
    {
        $this->authorize('delete', $property);

        $this->deleteImage($property->splash_image_path);
        $property->delete();

        return redirect()->back()->with('success', 'Property deleted successfully.');
    }
}
