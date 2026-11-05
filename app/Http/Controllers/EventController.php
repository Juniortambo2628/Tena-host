<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Events for event accounts (venues and organisers): each a dated event at
 * one of their venues, with an optional extra question and sponsor, and the
 * attendees who connected while it ran.
 */
class EventController extends Controller
{
    public function index(): Response
    {
        $user = $this->eventAccount();

        $events = Event::forHost($user)->with('property:id,name')->withCount('attendees')
            ->orderByDesc('starts_on')->get()
            ->map(fn (Event $e) => $this->summary($e));

        return Inertia::render('Host/Events/Index', [
            'events' => $events,
            'venues' => $user->sites()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(Event $event): Response
    {
        $this->eventAccount();
        $this->authorize('view', $event);
        $event->load('property:id,name')->loadCount('attendees');

        $attendees = $event->attendees()->orderByPivot('first_seen_at', 'desc')->paginate(25)
            ->through(fn ($g) => [
                'id' => $g->id,
                'name' => trim("{$g->first_name} {$g->last_name}"),
                'contact' => $g->phone ?: $g->email,
                'days' => count($g->pivot->days ?? []),
                'answer' => $g->pivot->answer,
                'first_seen_at' => $g->pivot->first_seen_at,
                // Joined the venue's list before this event started: a returning attendee.
                'returning' => $g->created_at && $g->created_at->lt($event->starts_on),
                'opted_in' => (bool) $g->marketing_opt_in,
            ]);

        $all = $event->attendees();

        return Inertia::render('Host/Events/Show', [
            'event' => $this->summary($event) + ['question' => $event->question, 'sponsor' => $event->sponsor],
            'attendees' => $attendees,
            'stats' => [
                'attendees' => $event->attendees_count,
                'returning' => (clone $all)->where('guests.created_at', '<', $event->starts_on)->count(),
                'optedIn' => (clone $all)->where('guests.marketing_opt_in', true)->count(),
                'answered' => (clone $all)->whereNotNull('event_guest.answer')->count(),
            ],
            'venues' => Auth::user()->sites()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->eventAccount();
        $event = Event::create($this->validated($request, $user) + ['user_id' => $user->accountOwner()->id]);

        return redirect()->route('host.events.show', $event)->with('success', "{$event->name} is set up.");
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        $user = $this->eventAccount();
        $this->authorize('update', $event);
        $event->update($this->validated($request, $user));

        return back()->with('success', 'Event saved.');
    }

    public function destroy(Event $event): RedirectResponse
    {
        $this->eventAccount();
        $this->authorize('delete', $event);
        $event->delete();

        return redirect()->route('host.events.index')->with('success', 'Event deleted.');
    }

    /** Events are for event accounts (Admin -> Settings -> Event accounts, or an admin setting the type). */
    private function eventAccount(): User
    {
        $user = Auth::user();
        abort_unless($user->isEventAccount() || $user->accountOwner()->isEventAccount(), 404);

        return $user;
    }

    private function validated(Request $request, User $user): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'property_id' => ['required', 'integer', Rule::in($user->propertyIds())],
            'starts_on' => 'required|date',
            'ends_on' => 'required|date|after_or_equal:starts_on',
            'question' => 'nullable|string|max:150',
            'sponsor.name' => 'nullable|string|max:100',
            'sponsor.message' => 'nullable|string|max:200',
            'sponsor.url' => 'nullable|url:http,https|max:300',
        ], [
            'property_id.in' => 'Pick one of your venues.',
            'ends_on.after_or_equal' => 'The last day can’t be before the first.',
        ]);

        $sponsor = array_filter($data['sponsor'] ?? [], fn ($v) => filled($v));
        $data['sponsor'] = $sponsor ?: null;

        return $data;
    }

    private function summary(Event $e): array
    {
        return [
            'id' => $e->id,
            'name' => $e->name,
            'property_id' => $e->property_id,
            'venue' => $e->property?->name,
            'starts_on' => $e->starts_on->toDateString(),
            'ends_on' => $e->ends_on->toDateString(),
            'status' => $e->status(),
            'attendees' => $e->attendees_count ?? 0,
        ];
    }
}
