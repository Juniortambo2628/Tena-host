<?php

namespace App\Http\Controllers;

use App\Mail\CampaignEmail;
use App\Models\Campaign;
use App\Models\Guest;
use App\Models\MarketingEvent;
use App\Services\CampaignAutomation;
use App\Services\CampaignDispatcher;
use App\Services\MarketingInsights;
use App\Services\Messaging\Messenger;
use App\Support\SplashDesign;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
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
        return Inertia::render('Host/Marketing/Builder', $this->studioProps(null));
    }

    /**
     * Store a newly created campaign.
     */
    public function store(Request $request, CampaignAutomation $automation)
    {
        $validated = $request->validate($this->rules(required: true));

        $campaign = Campaign::create([
            ...collect($validated)->except('launch')->all(),
            'user_id' => Auth::id(),
            'status' => 'draft',
        ]);

        if ($request->boolean('launch')) {
            return $this->launch($campaign, $automation);
        }

        return redirect()->route('host.marketing.edit', $campaign->id)
            ->with('success', 'Draft saved.');
    }

    /**
     * Show the campaign builder for editing an existing campaign.
     */
    public function edit($id)
    {
        $campaign = Campaign::where('user_id', Auth::id())->findOrFail($id);

        return Inertia::render('Host/Marketing/Builder', $this->studioProps($campaign));
    }

    /**
     * Update the specified campaign.
     */
    public function update(Request $request, CampaignAutomation $automation, $id)
    {
        $campaign = Campaign::where('user_id', Auth::id())->findOrFail($id);

        $validated = $request->validate($this->rules() + [
            // Going live always runs through activate(), which queues the sends.
            'status' => 'sometimes|in:draft,paused,archived',
        ]);

        $campaign->update(collect($validated)->except('launch')->all());

        if ($request->boolean('launch') && $campaign->status !== 'active') {
            return $this->launch($campaign, $automation);
        }

        return redirect()->back()->with('success', 'Campaign saved.');
    }

    /**
     * The campaign studio: properties, offers already written for the splash
     * pages (one tap to reuse), and which channels can actually send.
     *
     * @return array<string, mixed>
     */
    protected function studioProps(?Campaign $campaign): array
    {
        $properties = Auth::user()->properties()->orderBy('name')->get();
        $messenger = app(Messenger::class);

        return [
            'campaign' => $campaign,
            'properties' => $properties->map->only(['id', 'name']),
            'splashOffers' => $properties->flatMap(fn ($p) => collect(SplashDesign::forProperty($p)['content']['offers'])
                ->map(fn ($o) => ['property_id' => $p->id, 'property' => $p->name] + $o))->values(),
            'channels' => [
                'email' => true,
                'whatsapp' => $messenger->configured('whatsapp'),
                'sms' => $messenger->configured('sms'),
            ],
        ];
    }

    /**
     * Render an unsaved campaign email exactly as guests get it, for a sample guest.
     */
    public function preview(Request $request)
    {
        $data = $request->validate([
            'subject' => 'nullable|string|max:255',
            'name' => 'nullable|string|max:255',
            'content' => 'nullable|string|max:5000',
            'property_id' => ['nullable', 'integer', Rule::in(Auth::user()->propertyIds())],
        ]);

        $property = Auth::user()->properties()->find($data['property_id'] ?? null) ?? Auth::user()->properties()->first();
        $guest = new Guest(['first_name' => 'Wanjiru', 'last_name' => 'Kamau', 'email' => 'wanjiru@example.com']);
        $guest->setRelation('property', $property);
        $campaign = new Campaign(['type' => 'email', 'name' => $data['name'] ?? '', 'subject' => $data['subject'] ?? '', 'content' => $data['content'] ?? '']);

        return response()->view('emails.campaign', CampaignEmail::viewData($campaign, $guest));
    }

    /**
     * Shared by the studio's create and edit saves.
     *
     * @return array<string, mixed>
     */
    protected function rules(bool $required = false): array
    {
        $own = Rule::in(Auth::user()->propertyIds());
        $need = $required ? 'required' : 'sometimes';

        return [
            'name' => "{$need}|string|max:255",
            'type' => "{$need}|in:email,sms,whatsapp",
            'subject' => 'nullable|string|max:255',
            'content' => 'nullable|string|max:5000',
            'trigger_event' => ['nullable', Rule::in([CampaignAutomation::CONNECTS, CampaignAutomation::BEFORE_ARRIVAL, CampaignAutomation::CHECKOUT_DAY, CampaignAutomation::CUSTOM_DATE])],
            'trigger_delay' => 'nullable|in:Instant,1 Hour,6 Hours,24 Hours,3 Days,7 Days',
            'target_audience' => 'nullable|in:all_guests,property_guests,new_guests,returning_guests,vip_guests',
            'property_id' => ['nullable', 'integer', $own],
            'audience_property_id' => ['nullable', 'integer', $own],
            'audience_from' => 'nullable|date',
            'audience_to' => 'nullable|date|after_or_equal:audience_from',
            'schedule_trigger' => 'nullable|string|max:255',
            'scheduled_at' => 'nullable|date',
            'launch' => 'sometimes|boolean',
        ];
    }

    protected function launch(Campaign $campaign, CampaignAutomation $automation)
    {
        if (blank($campaign->content)) {
            return redirect()->route('host.marketing.edit', $campaign->id)
                ->with('error', 'Saved as a draft: write the message before launching.');
        }

        $queued = $automation->activate($campaign);

        return redirect()->route('host.marketing.index')->with('success', $this->launchedMessage($campaign, $queued));
    }

    protected function launchedMessage(Campaign $campaign, int $queued): string
    {
        return match (true) {
            ! CampaignAutomation::isBroadcast($campaign) => 'Campaign is live. Each guest gets it after "'.$campaign->trigger_event.'".',
            $queued === 0 && $campaign->scheduled_at?->isFuture() => 'Campaign scheduled for '.$campaign->scheduled_at->format('j M Y, H:i').'.',
            default => "Campaign activated. {$queued} guests queued for delivery.",
        };
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

        return redirect()->back()->with('success', $this->launchedMessage($campaign, $queued));
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
