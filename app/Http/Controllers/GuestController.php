<?php

namespace App\Http\Controllers;

use App\Models\Guest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class GuestController extends Controller
{
    /**
     * Display a listing of the guests for the host's properties.
     */
    public function index(Request $request)
    {
        $guests = Guest::forHost(Auth::user())
            ->with('property:id,name')
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(15);

        return Inertia::render('Host/Guests/Index', [
            'guests' => $guests,
            'filters' => $request->only(['search']),
            'properties' => Auth::user()->properties,
        ]);
    }

    /**
     * Store a newly created guest.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'property_id' => 'required|exists:properties,id',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        $this->authorize('create', Guest::class);

        $guest = Guest::create($validated);

        return redirect()->back()->with('success', 'Guest added successfully.');
    }

    /**
     * Display the specified guest.
     */
    public function show(Guest $guest)
    {
        $this->authorize('view', $guest);

        return Inertia::render('Host/Guests/Show', [
            'guest' => $guest->load('property:id,name,address'),
        ]);
    }

    /**
     * Update the specified guest.
     */
    public function update(Request $request, Guest $guest)
    {
        $this->authorize('update', $guest);

        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        $guest->update($validated);

        return redirect()->back()->with('success', 'Guest updated successfully.');
    }

    /**
     * Remove the specified guest.
     */
    public function destroy(Guest $guest)
    {
        $this->authorize('delete', $guest);
        $guest->delete();

        return redirect()->back()->with('success', 'Guest deleted successfully.');
    }
}
