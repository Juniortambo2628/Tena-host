<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\VisitorVisit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The reception visitor log: a day's visits at the sites that keep one (who
 * came, who they came to see, in and out times), with walk-ins added by hand.
 * Team members (e.g. reception staff) see the sites they were given.
 */
class VisitorController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless(Property::visitorLogEnabled(), 404);
        $user = Auth::user();
        $sites = $user->sites()->where('visitor_log', true)->orderBy('name')->get(['id', 'name']);

        $request->validate(['date' => 'nullable|date_format:Y-m-d', 'site' => 'nullable|integer']);
        $day = $request->filled('date') ? Carbon::parse($request->input('date')) : today();
        $site = $sites->firstWhere('id', (int) $request->input('site'));

        $visits = VisitorVisit::forHost($user)->with('property:id,name')
            ->whereIn('property_id', $site ? [$site->id] : $sites->pluck('id'))
            ->whereBetween('arrived_at', [$day->copy()->startOfDay(), $day->copy()->endOfDay()])
            ->latest('arrived_at')->limit(500)->get()
            ->map(fn (VisitorVisit $v) => [
                'id' => $v->id,
                'name' => $v->name,
                'contact' => $v->contact,
                'visiting' => $v->visiting,
                'site' => $v->property?->name,
                'arrived_at' => $v->arrived_at->toIso8601String(),
                'left_at' => $v->left_at?->toIso8601String(),
                'walk_in' => $v->guest_id === null,
            ]);

        return Inertia::render('Host/Visitors/Index', [
            'visits' => $visits,
            'sites' => $sites,
            'filters' => ['date' => $day->toDateString(), 'site' => $site?->id],
            'isToday' => $day->isToday(),
        ]);
    }

    /** A walk-in reception adds by hand (no phone on the WiFi). */
    public function store(Request $request): RedirectResponse
    {
        abort_unless(Property::visitorLogEnabled(), 404);
        $sites = Auth::user()->sites()->where('visitor_log', true)->pluck('id')->all();

        $data = $request->validate([
            'property_id' => ['required', 'integer', Rule::in($sites)],
            'name' => 'required|string|max:100',
            'contact' => 'nullable|string|max:150',
            'visiting' => 'required|string|max:100',
        ], [
            'property_id.in' => 'Pick one of your sites.',
            'visiting.required' => 'Add who they’re here to see.',
        ]);

        VisitorVisit::create($data + ['arrived_at' => now()]);

        return back()->with('success', "{$data['name']} is signed in.");
    }

    /** Reception signs a visitor out. */
    public function leave(VisitorVisit $visit): RedirectResponse
    {
        abort_unless(Property::visitorLogEnabled(), 404);
        abort_unless(in_array($visit->property_id, Auth::user()->propertyIds(), true), 404);

        if (! $visit->left_at) {
            $visit->update(['left_at' => now()]);
        }

        return back()->with('success', "{$visit->name} is signed out.");
    }
}
