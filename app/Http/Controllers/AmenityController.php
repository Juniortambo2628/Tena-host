<?php

namespace App\Http\Controllers;

use App\Models\Amenity;
use App\Models\Property;
use App\Traits\HasImageUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AmenityController extends Controller
{
    use HasImageUpload;

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
            $validated['image_path'] = $this->storeImage($request->file('image'), 'amenities');
        }

        Amenity::create($validated);

        return redirect()->back()->with('success', 'Amenity added successfully.');
    }

    public function show(Amenity $amenity)
    {
        $this->authorize('view', $amenity);

        return Inertia::render('Host/Amenities/Show', [
            'amenity' => $amenity->load('property:id,name,address'),
        ]);
    }

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
            $validated['image_path'] = $this->updateImage(
                $request->file('image'),
                $amenity->image_path,
                'amenities'
            );
        }

        $amenity->update($validated);

        return redirect()->back()->with('success', 'Amenity updated successfully.');
    }

    public function destroy(Amenity $amenity)
    {
        $this->authorize('delete', $amenity);

        $this->deleteImage($amenity->image_path);
        $amenity->delete();

        return redirect()->back()->with('success', 'Amenity deleted successfully.');
    }
}
