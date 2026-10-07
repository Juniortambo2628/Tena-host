<?php

namespace App\Http\Controllers;

use App\Models\Guest;
use App\Models\Order;
use App\Support\Phone;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
            'email' => 'nullable|required_without:phone|email|max:255',
            'phone' => 'nullable|required_without:email|string|max:20',
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
            'activity' => static::activity($guest),
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
            'email' => 'sometimes|nullable|email|max:255',
            'phone' => 'sometimes|nullable|string|max:20',
            'notes' => 'sometimes|nullable|string|max:5000',
        ]);

        if (array_key_exists('phone', $validated)) {
            $validated['phone'] = Phone::toE164($validated['phone']);
        }

        $guest->update($validated);

        return redirect()->back()->with('success', 'Guest updated successfully.');
    }

    /**
     * What we know happened with this guest, newest first.
     *
     * @return list<array{at: string, label: string, detail: ?string}>
     */
    protected static function activity(Guest $guest): array
    {
        $events = collect([
            [$guest->created_at, 'First seen', 'Added via '.($guest->source ?: 'WiFi')],
            [$guest->consented_at, 'Agreed to be contacted', $guest->marketing_opt_in ? 'Opted in to offers' : 'Visit messages only'],
            [$guest->check_in, 'Booking: check-in', null],
            [$guest->check_out, 'Booking: check-out', null],
            [$guest->last_connected, 'Last connected to the WiFi', $guest->total_visits.' visit'.($guest->total_visits === 1 ? '' : 's').' in total'],
            [$guest->review_requested_at, 'Review request sent', $guest->review_clicked_at ? 'Opened the review link' : null],
        ]);

        $guest->campaignRecipients()->with('campaign:id,name,type')->get()->each(fn ($r) => $events->push([
            $r->created_at,
            'Campaign: '.($r->campaign?->name ?? 'deleted'),
            ucfirst((string) $r->campaign?->type).($r->clicked_at ? ' · clicked' : ($r->opened_at ? ' · opened' : '')),
        ]));

        Order::with('amenity:id,name')->where('guest_id', $guest->id)->get()->each(fn (Order $o) => $events->push([
            $o->created_at,
            'Ordered '.($o->amenity?->name ?? 'an extra'),
            'KES '.number_format((float) $o->total).' · '.str_replace('_', ' ', $o->payment_status),
        ]));

        return $events->filter(fn ($e) => $e[0])
            ->map(fn ($e) => ['at' => Carbon::parse($e[0])->toIso8601String(), 'label' => $e[1], 'detail' => $e[2]])
            ->sortByDesc('at')->values()->all();
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
