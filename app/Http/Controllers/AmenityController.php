<?php

namespace App\Http\Controllers;

use App\Models\Amenity;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class AmenityController extends Controller
{
    /**
     * Display a listing of amenities for the host's properties.
     */
    public function index(Request $request)
    {
        $amenities = Amenity::forHost(Auth::user())
            ->with('property:id,name')
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(15);

        return Inertia::render('Host/Amenities/Index', [
            'amenities' => $amenities,
            'filters' => $request->only(['search']),
            'properties' => Auth::user()->properties,
        ]);
    }

    /**
     * Store a newly created amenity.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'property_id' => 'required|exists:properties,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'price' => 'required|numeric|min:0',
            'is_active' => 'boolean',
            'image' => 'nullable|image|max:2048',
        ]);

        $property = Property::findOrFail($validated['property_id']);
        $this->authorize('update', $property);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('amenities', 'public');
            $validated['image_path'] = '/storage/'.$path;
        }

        Amenity::create($validated);

        return redirect()->back()->with('success', 'Amenity added successfully.');
    }

    /**
     * Display the specified amenity.
     */
    public function show(Amenity $amenity)
    {
        $this->authorize('view', $amenity);

        return Inertia::render('Host/Amenities/Show', [
            'amenity' => $amenity->load('property:id,name,address'),
        ]);
    }

    /**
     * Update the specified amenity.
     */
    public function update(Request $request, Amenity $amenity)
    {
        $this->authorize('update', $amenity);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:1000',
            'price' => 'sometimes|numeric|min:0',
            'is_active' => 'boolean',
            'image' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            if ($amenity->image_path) {
                $oldPath = str_replace('/storage/', '', $amenity->image_path);
                Storage::disk('public')->delete($oldPath);
            }

            $path = $request->file('image')->store('amenities', 'public');
            $validated['image_path'] = '/storage/'.$path;
        }

        $amenity->update($validated);

        return redirect()->back()->with('success', 'Amenity updated successfully.');
    }

    /**
     * Remove the specified amenity.
     */
    public function destroy(Amenity $amenity)
    {
        $this->authorize('delete', $amenity);

        if ($amenity->image_path) {
            $oldPath = str_replace('/storage/', '', $amenity->image_path);
            Storage::disk('public')->delete($oldPath);
        }

        $amenity->delete();

        return redirect()->back()->with('success', 'Amenity deleted successfully.');
    }
}
