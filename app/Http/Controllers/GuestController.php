<?php

namespace App\Http\Controllers;

use App\Models\Guest;
use App\Models\Order;
use App\Support\Phone;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
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
                        ->orWhere('email', 'like', "%{$search}%")
                        // Phones are stored as +2547…; match "0712 345", "712345" or "+254712".
                        ->when(strlen($digits = ltrim(preg_replace('/\D/', '', $search), '0')) >= 3,
                            fn ($q) => $q->orWhere('phone', 'like', "%{$digits}%"));
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
     * Add a guest by hand (walk-in, phone booking). Only into the host's own
     * properties; a guest already on file with the same phone or email is updated.
     */
    public function store(Request $request)
    {
        $request->merge(['phone' => Phone::toE164($request->input('phone'))]);
        $validated = $request->validate([
            'property_id' => ['required', 'integer', Rule::in(Auth::user()->propertyIds())],
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'nullable|required_without:phone|email|max:255',
            'phone' => ['nullable', 'required_without:email', 'string', 'max:20', $this->validPhone()],
            'contact_consent' => 'accepted',
            'marketing_opt_in' => 'nullable|boolean',
        ], [
            'property_id.in' => 'Choose one of your properties.',
            'email.required_without' => 'Add a phone number or an email address.',
            'phone.required_without' => 'Add a phone number or an email address.',
            'contact_consent.accepted' => 'Confirm the guest agreed to be contacted.',
        ]);

        $this->authorize('create', Guest::class);

        $email = filled($validated['email'] ?? null) ? strtolower(trim($validated['email'])) : null;
        $guest = Guest::where('property_id', $validated['property_id'])
            ->where(fn ($q) => $q->when($validated['phone'], fn ($q, $phone) => $q->where('phone', $phone))
                ->when($email, fn ($q) => $q->orWhere('email', $email)))
            ->first() ?? new Guest(['property_id' => $validated['property_id'], 'source' => 'Manual', 'total_visits' => 0]);
        $existed = $guest->exists;

        $guest->fill([
            'first_name' => trim($validated['first_name']),
            'last_name' => $validated['last_name'] ?? $guest->last_name,
            'email' => $email ?? $guest->email,
            'phone' => $validated['phone'] ?? $guest->phone,
            // Same rule as the WiFi form: an opt-in sticks once given.
            'marketing_opt_in' => $guest->marketing_opt_in || ! empty($validated['marketing_opt_in']),
            'consent_text' => 'Added by the host, who confirmed the guest agreed to be contacted about their stay'
                .(! empty($validated['marketing_opt_in']) ? ' and to receive offers.' : '.'),
            'consented_at' => now(),
        ])->save();

        return redirect()->back()->with('success', $existed ? 'That guest was already on file, so their details were updated.' : 'Guest added successfully.');
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

        if ($request->has('phone')) {
            $request->merge(['phone' => Phone::toE164($request->input('phone'))]);
        }

        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|nullable|email|max:255',
            'phone' => ['sometimes', 'nullable', 'string', 'max:20', $this->validPhone()],
            'notes' => 'sometimes|nullable|string|max:5000',
        ]);

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

    /** Phones are stored as E.164 so WhatsApp/SMS and duplicate checks work. */
    protected function validPhone(): \Closure
    {
        return function ($attribute, $value, $fail) {
            if ($value && ! Phone::isValid($value)) {
                $fail('Enter a valid phone number, e.g. 0712 345 678 or +44 7700 900123.');
            }
        };
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
