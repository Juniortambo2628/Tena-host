<?php

namespace App\Http\Controllers;

use App\Models\AccessPoint;
use App\Models\Analytics;
use App\Models\Event;
use App\Models\Property;
use App\Services\GuestCaptureService;
use App\Services\Unifi\UnifiService;
use App\Support\Brand;
use App\Support\Phone;
use App\Support\SplashDesign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Public external captive portal for Ubiquiti UniFi.
 *
 * The UniFi AP/controller redirects a connecting device to GET /portal with
 * query params describing the client. On a property TenaFi knows (its AP is
 * registered), the guest opts in with their name and WhatsApp number before
 * POST /portal/connect authorizes the device; a returning device reconnects
 * with one tap. Unknown APs fall back to a plain connect button.
 *
 * Errors re-render the page instead of redirecting with session errors:
 * captive-portal browsers often drop cookies.
 */
class WifiPortalController extends Controller
{
    public function __construct(
        protected UnifiService $unifi,
        protected GuestCaptureService $guests,
    ) {}

    /**
     * Show the splash page. UniFi appends: id (client MAC), ap (AP MAC),
     * t (timestamp), url (original destination), ssid.
     */
    public function show(Request $request)
    {
        $context = $this->context($request);

        // Sponsor report: a real device (a known access point) saw the sponsor on the page.
        if (! empty($context['event']?->sponsor['name']) && $context['canConnect']) {
            rescue(fn () => Analytics::bump($context['event']->metric('sponsor_views')), report: false);
        }

        return view('portal.splash', $context + [
            'error' => $request->query('error'),
        ]);
    }

    /**
     * The sponsor's link on the WiFi page: counted for the sponsor report,
     * then on to the sponsor's own page.
     */
    public function sponsor(Event $event)
    {
        $url = $event->sponsor['url'] ?? null;
        abort_unless($url && preg_match('#^https?://#i', $url), 404);
        rescue(fn () => Analytics::bump($event->metric('sponsor_clicks')), report: false);

        return redirect()->away($url);
    }

    /**
     * UniFi's own redirect path (/guest/s/{site}/), used by controllers that
     * append it to the external portal address. Forwards to /portal with the
     * query string unchanged.
     */
    public function unifiPath(Request $request)
    {
        return redirect()->route('portal.show', $request->query());
    }

    /**
     * Owner-only preview. GET shows the saved design (optionally in another
     * template); POST renders an unsaved studio draft without storing it.
     */
    public function preview(Request $request, Property $property)
    {
        $this->authorize('update', $property);
        $data = $request->validate(['template' => 'nullable|in:'.implode(',', array_merge(SplashDesign::TEMPLATES, array_keys(SplashDesign::LEGACY)))]
            + ($request->isMethod('post') ? SplashDesign::rules() + ['review_url' => 'nullable|url:https|max:500', 'page' => 'nullable|in:splash,connected'] : []));

        if ($request->isMethod('post')) {
            // Never saved: the draft only lives on this in-memory model.
            $property->branding_json = SplashDesign::clean($data['branding_json'] ?? []);
            $property->review_url = $data['review_url'] ?? null;
        }

        return view(($data['page'] ?? 'splash') === 'connected' ? 'portal.connected' : 'portal.splash', array_replace($this->context(new Request), [
            'property' => $property,
            'propertyName' => $property->name,
            'logoUrl' => SplashDesign::imageUrl($property->logo_path),
            'splashImageUrl' => SplashDesign::imageUrl($property->splash_image_path),
            'design' => SplashDesign::forProperty($property, $data['template'] ?? null),
            'consentText' => static::consentText($property->name),
            'isBusiness' => SplashDesign::isBusiness($property),
            'preview' => true,
            // The studio frames the draft and labels it itself.
            'embedded' => $request->isMethod('post'),
            'canConnect' => false,
        ]));
    }

    /**
     * Authorize the device against the UniFi controller and send it on its way.
     */
    public function connect(Request $request)
    {
        $data = $request->validate([
            'id' => ['required', 'string', 'regex:/^(?:[0-9a-fA-F]{12}|(?:[0-9a-fA-F]{2}[:-]){5}[0-9a-fA-F]{2})$/'],
            'ap' => ['required', 'string', 'regex:/^(?:[0-9a-fA-F]{12}|(?:[0-9a-fA-F]{2}[:-]){5}[0-9a-fA-F]{2})$/'],
            't' => 'nullable|string|max:32',
            'ssid' => 'nullable|string|max:64',
            'url' => 'nullable|string|max:2048', // original destination
        ]);

        $context = $this->context($request);

        if (! $context['property']) {
            return response()->view('portal.splash', $context + [
                'error' => 'This access point is not linked to a property yet. Please ask your host to register it.',
            ], 422);
        }

        $guestData = null;

        if ($context['property'] && ! $context['returningGuest']) {
            $request->merge(['phone' => Phone::toE164($request->input('phone'))]);
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string|max:60',
                'phone' => ['required', 'string', function ($attribute, $value, $fail) {
                    if (! Phone::isValid($value)) {
                        $fail('Enter a valid WhatsApp number, e.g. 712 345 678.');
                    }
                }],
                'email' => 'nullable|email|max:150',
                'consent' => 'accepted',
                'marketing_opt_in' => 'nullable|boolean',
                'birthday_month' => 'nullable|integer|between:1,12|required_with:birthday_day',
                'birthday_day' => 'nullable|integer|between:1,31|required_with:birthday_month',
                // The running event's extra question (optional for the guest).
                'event_answer' => 'nullable|string|max:255',
            ], [
                'first_name.required' => 'Please add your first name.',
                'phone.required' => 'Please add your WhatsApp number.',
                'consent.accepted' => 'Please tick the box to agree before connecting.',
            ]);

            if ($validator->fails()) {
                return response()->view('portal.splash', $context + [
                    'errors' => $validator->errors(),
                    'old' => $request->only('first_name', 'phone', 'email', 'marketing_opt_in', 'birthday_month', 'birthday_day', 'event_answer'),
                ], 422);
            }

            $guestData = $validator->validated();

            // Only keep what the page asked for: clinic pages never collect email or birthdays.
            $fields = $context['design']['fields'];
            if (! $fields['email']) {
                unset($guestData['email']);
            }
            if (! $fields['birthday']) {
                unset($guestData['birthday_month'], $guestData['birthday_day']);
            }
            $eventAnswer = $context['event']?->question ? ($guestData['event_answer'] ?? null) : null;
            unset($guestData['event_answer']);
        }

        try {
            $authorized = $this->unifi->authorizeGuest(
                clientMac: $data['id'],
                apMac: $data['ap'] ?? null,
            );
        } catch (\Throwable $e) {
            Log::error('WiFi portal authorize error: '.$e->getMessage(), [
                'client_mac' => $data['id'],
                'ap_mac' => $data['ap'] ?? null,
            ]);
            $authorized = false;
        }

        if (! $authorized) {
            return response()->view('portal.splash', $context + [
                'error' => 'We could not connect you to the WiFi. Please try again.',
                'old' => $request->only('first_name', 'phone', 'email', 'consent', 'marketing_opt_in', 'birthday_month', 'birthday_day', 'event_answer'),
            ], 503);
        }

        // A failed controller request must not create a guest or a successful visit.
        $guest = null;
        if ($guestData !== null) {
            $guest = $this->guests->capture($context['property'], $guestData, $context['consentText'], $context['deviceMac']);
        } elseif ($context['returningGuest']) {
            $guest = $context['returningGuest'];
            $this->guests->recordVisit($guest, $context['deviceMac']);
        }

        // During an event at this venue, the sign-in joins its attendee list (events must never block the connection).
        if ($guest && $context['event']) {
            try {
                $context['event']->recordAttendance($guest, $eventAnswer ?? null);
            } catch (\Throwable $e) {
                Log::error('Event attendance not recorded: '.$e->getMessage(), ['event' => $context['event']->id, 'guest' => $guest->id]);
            }
        }

        // Best-effort: mark the AP as recently seen.
        if (! empty($data['ap'])) {
            AccessPoint::where('mac_address', $this->unifi->normalizeMac($data['ap']))
                ->update(['last_seen' => now(), 'status' => 'online']);
        }

        // Business customers land on the business's homepage (menu, offers).
        if (SplashDesign::isBusiness($context['property'])) {
            return redirect()->route('venue.show', $context['property']);
        }

        // Send the device back to where it was headed, or to a success page.
        // When the host has guest content (house info, local picks, offers), the
        // success page carries it, with a link on to the original destination.
        $target = $data['url'] ?? null;
        $target = $target && preg_match('#^https?://#i', $target) ? $target : null;
        if ($target && ! $context['design']['hasContent']) {
            return redirect()->away($target);
        }

        return view('portal.connected', $context + ['continueUrl' => $target]);
    }

    /**
     * Resolve branding + the UniFi params for the view.
     */
    protected function context(Request $request): array
    {
        $params = [
            'id' => $request->input('id'),
            'ap' => $request->input('ap'),
            't' => $request->input('t'),
            'ssid' => $request->input('ssid'),
            'url' => $request->input('url'),
        ];

        $property = null;
        if (! empty($params['ap'])) {
            $property = AccessPoint::with('property')
                ->where('mac_address', $this->unifi->normalizeMac($params['ap']))
                ->first()?->property;
        }

        $deviceMac = ! empty($params['id']) ? $this->unifi->normalizeMac($params['id']) : null;
        $propertyName = $property?->name ?? Brand::name();

        // An event running at this venue today: its welcome, question and sponsor on the page.
        $event = $property ? Event::runningAt($property) : null;
        $design = SplashDesign::forProperty($property);
        if ($event) {
            $welcome = $event->welcome();
            $design['heading'] = $welcome['heading'];
            $design['message'] = $welcome['message'] ?? $design['message'];
        }

        return [
            'params' => $params,
            'property' => $property,
            'propertyName' => $propertyName,
            'logoUrl' => SplashDesign::imageUrl($property?->logo_path),
            'splashImageUrl' => SplashDesign::imageUrl($property?->splash_image_path),
            'design' => $design,
            'event' => $event,
            'canConnect' => $property && is_string($params['id']) && (bool) preg_match('/^(?:[0-9a-fA-F]{12}|(?:[0-9a-fA-F]{2}[:-]){5}[0-9a-fA-F]{2})$/', $params['id']),
            'deviceMac' => $deviceMac,
            'returningGuest' => $property ? $this->guests->findReturning($property, $deviceMac) : null,
            'consentText' => static::consentText($propertyName),
            'isBusiness' => SplashDesign::isBusiness($property),
        ];
    }

    /**
     * The exact wording shown next to the consent checkbox, stored with each
     * guest. Review requests rely on it, so it names them.
     */
    public static function consentText(string $propertyName): string
    {
        return "I agree that {$propertyName} may store my details and contact me by WhatsApp, SMS or email about my visit, including a request for a review. Privacy policy applies.";
    }

    /**
     * The subset of UniFi params worth preserving across a failed attempt.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function passthrough(array $data): array
    {
        return array_filter([
            'id' => $data['id'] ?? null,
            'ap' => $data['ap'] ?? null,
            't' => $data['t'] ?? null,
            'ssid' => $data['ssid'] ?? null,
            'url' => $data['url'] ?? null,
        ]);
    }
}
