<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\MarketingEvent;
use App\Services\CampaignAutomation;
use App\Services\CampaignDispatcher;
use App\Services\MarketingInsights;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class MarketingController extends Controller
{
    /**
     * Display the marketing campaign dashboard.
     */
    public function index()
    {
        $propertyIds = Auth::user()->properties()->pluck('id');

        $campaigns = Campaign::whereIn('user_id', [Auth::id()])
            ->orWhereIn('property_id', $propertyIds)
            ->latest()
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'type' => ucfirst($c->type),
                'status' => ucfirst($c->status),
                'performance' => $c->total_sent > 0
                    ? $c->open_rate.'% Open rate'
                    : '-',
                'last_sent' => $c->updated_at->isToday()
                    ? $c->updated_at->diffForHumans()
                    : ($c->updated_at->isYesterday() ? 'Yesterday' : $c->updated_at->format('M j')),
            ]);

        $reachable = app(CampaignDispatcher::class)
            ->audienceQuery(new Campaign(['user_id' => Auth::id(), 'type' => 'whatsapp']))->count();

        $totalSent = Campaign::where('user_id', Auth::id())->sum('total_sent');
        $totalOpened = Campaign::where('user_id', Auth::id())->sum('total_opened');
        $totalClicked = Campaign::where('user_id', Auth::id())->sum('total_clicked');

        return Inertia::render('Host/Marketing/Index', [
            'campaigns' => $campaigns,
            'stats' => [
                'totalSent' => $totalSent,
                'avgOpenRate' => $totalSent > 0 ? round(($totalOpened / $totalSent) * 100, 1).'%' : '0%',
                'clicks' => $totalClicked,
                'reachable' => $reachable,
            ],
            'insights' => app(MarketingInsights::class)->forHost(Auth::user()),
            'triggers' => collect([CampaignAutomation::CONNECTS, CampaignAutomation::BEFORE_ARRIVAL, CampaignAutomation::CHECKOUT_DAY])
                ->map(fn ($trigger) => [
                    'title' => $trigger,
                    'active' => Campaign::where('user_id', Auth::id())->where('status', 'active')->where('trigger_event', $trigger)->count(),
                ])->all(),
        ]);
    }

    /**
     * How many guests a draft campaign would reach (builder "Estimated reach").
     */
    public function estimate(Request $request, CampaignDispatcher $dispatcher)
    {
        $data = $request->validate([
            'type' => 'required|in:email,sms,whatsapp',
            'target_audience' => 'nullable|string|max:32',
            'audience_property_id' => 'nullable|integer',
            'audience_from' => 'nullable|date',
            'audience_to' => 'nullable|date',
        ]);

        $campaign = new Campaign($data + ['user_id' => Auth::id()]);

        return response()->json(['count' => $dispatcher->audienceQuery($campaign)->count()]);
    }

    /**
     * Show the campaign builder for creating a new campaign.
     */
    public function create()
    {
        return Inertia::render('Host/Marketing/Builder', [
            'campaign' => null,
            'properties' => Auth::user()->properties,
        ]);
    }

    /**
     * Store a newly created campaign.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:email,sms,whatsapp',
            'subject' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'trigger_event' => 'nullable|string',
            'trigger_delay' => 'nullable|string',
            'target_audience' => 'nullable|string',
            'property_id' => 'nullable|exists:properties,id',
            'audience_property_id' => 'nullable|exists:properties,id',
            'audience_from' => 'nullable|date',
            'audience_to' => 'nullable|date|after_or_equal:audience_from',
            'schedule_trigger' => 'nullable|string',
            'scheduled_at' => 'nullable|date',
        ]);

        $campaign = Campaign::create([
            ...$validated,
            'user_id' => Auth::id(),
            'status' => 'draft',
        ]);

        return redirect()->route('host.marketing.index')
            ->with('success', 'Campaign created successfully.');
    }

    /**
     * Show the campaign builder for editing an existing campaign.
     */
    public function edit($id)
    {
        $campaign = Campaign::where('user_id', Auth::id())->findOrFail($id);

        return Inertia::render('Host/Marketing/Builder', [
            'campaign' => $campaign,
            'properties' => Auth::user()->properties,
        ]);
    }

    /**
     * Update the specified campaign.
     */
    public function update(Request $request, $id)
    {
        $campaign = Campaign::where('user_id', Auth::id())->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:email,sms,whatsapp',
            'status' => 'sometimes|in:draft,active,paused,archived',
            'subject' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'trigger_event' => 'nullable|string',
            'trigger_delay' => 'nullable|string',
            'target_audience' => 'nullable|string',
            'property_id' => 'nullable|exists:properties,id',
            'audience_property_id' => 'nullable|exists:properties,id',
            'audience_from' => 'nullable|date',
            'audience_to' => 'nullable|date|after_or_equal:audience_from',
            'schedule_trigger' => 'nullable|string',
            'scheduled_at' => 'nullable|date',
        ]);

        $campaign->update($validated);

        return redirect()->back()->with('success', 'Campaign updated successfully.');
    }

    /**
     * Activate and dispatch a campaign immediately.
     */
    public function activate($id)
    {
        $campaign = Campaign::findOrFail($id);
        $this->authorize('manage', $campaign);

        if ($campaign->status === 'active') {
            return redirect()->back()->with('info', 'Campaign is already active.');
        }

        $queued = app(CampaignAutomation::class)->activate($campaign);

        $message = match (true) {
            ! CampaignAutomation::isBroadcast($campaign) => 'Campaign is live. Each guest gets it after "'.$campaign->trigger_event.'".',
            $queued === 0 && $campaign->scheduled_at?->isFuture() => 'Campaign scheduled for '.$campaign->scheduled_at->format('j M Y, H:i').'.',
            default => "Campaign activated. {$queued} guests queued for delivery.",
        };

        return redirect()->back()->with('success', $message);
    }

    /**
     * Pause an active campaign.
     */
    public function pause($id)
    {
        $campaign = Campaign::findOrFail($id);
        $this->authorize('manage', $campaign);

        $campaign->update(['status' => 'paused']);

        return redirect()->back()->with('success', 'Campaign paused successfully.');
    }

    /**
     * Remove the specified campaign.
     */
    public function destroy($id)
    {
        $campaign = Campaign::findOrFail($id);
        $this->authorize('manage', $campaign);

        $campaign->delete();

        return redirect()->back()->with('success', 'Campaign deleted successfully.');
    }

    /**
     * Show campaign analytics.
     */
    public function analytics($id)
    {
        $campaign = Campaign::findOrFail($id);
        $this->authorize('manage', $campaign);

        $eventsByDay = MarketingEvent::where('campaign_id', $campaign->id)
            ->selectRaw('DATE(created_at) as date, event_type, COUNT(*) as count')
            ->groupBy('date', 'event_type')
            ->orderBy('date')
            ->get()
            ->groupBy('date')
            ->map(function ($dayEvents) {
                $day = $dayEvents->first()->date;

                return [
                    'name' => Carbon::parse($day)->format('D'),
                    'delivered' => $dayEvents->where('event_type', 'sent')->sum('count'),
                    'opened' => $dayEvents->where('event_type', 'opened')->sum('count'),
                    'clicks' => $dayEvents->where('event_type', 'clicked')->sum('count'),
                ];
            })
            ->values();

        return Inertia::render('Host/Marketing/Analytics', [
            'campaign' => [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'type' => ucfirst($campaign->type),
            ],
            'performance' => $eventsByDay,
            'summary' => [
                'totalDelivered' => $campaign->total_sent,
                'openRate' => $campaign->open_rate.'%',
                'clickRate' => $campaign->click_rate.'%',
                'unsubscribes' => '0%',
            ],
        ]);
    }
}
