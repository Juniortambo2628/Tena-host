<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Pay per event, TenaFi's side: events organisers asked to have priced,
 * the price set here (the organiser is told), and which have been paid.
 */
class EventQuoteController extends Controller
{
    public function index(): Response
    {
        $events = Event::with(['owner:id,first_name,last_name,email,phone_number', 'property:id,name'])
            ->whereNotNull('quote_status')
            ->orderByRaw("case quote_status when 'requested' then 0 when 'quoted' then 1 else 2 end")
            ->orderByDesc('quote_requested_at')->limit(200)->get()
            ->map(fn (Event $e) => [
                'id' => $e->id,
                'name' => $e->name,
                'venue' => $e->property?->name,
                'organiser' => trim("{$e->owner?->first_name} {$e->owner?->last_name}") ?: $e->owner?->email,
                'contact' => $e->owner?->phone_number ?: $e->owner?->email,
                'starts_on' => $e->starts_on->toDateString(),
                'ends_on' => $e->ends_on->toDateString(),
                'expected_attendees' => $e->expected_attendees,
                'request_note' => $e->request_note,
                'status' => $e->quote_status,
                'amount' => $e->quote_amount,
                'quote_note' => $e->quote_note,
                'requested_at' => $e->quote_requested_at?->toIso8601String(),
                'paid_at' => $e->paid_at?->toIso8601String(),
            ]);

        return Inertia::render('Admin/EventQuotes/Index', [
            'events' => $events,
            'enabled' => Event::billingEnabled(),
        ]);
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        abort_if($event->paid_at || ! $event->quote_status, 404);

        $data = $request->validate([
            'quote_amount' => 'required|integer|min:1|max:10000000',
            'quote_note' => 'nullable|string|max:300',
        ], ['quote_amount.required' => 'Set the price in KES.']);

        $event->forceFill($data + ['quote_status' => 'quoted', 'quoted_at' => now()])->save();
        NotificationService::eventQuoted($event);

        return back()->with('success', "{$event->name} is priced at KES ".number_format($event->quote_amount).'. The organiser has been told.');
    }
}
