<?php

namespace App\Http\Controllers;

use App\Models\AccessPoint;
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

        return view('portal.splash', $context + [
            'error' => $request->query('error'),
        ]);
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
            'isBusiness' => (bool) $property->host?->isBusiness(),
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
            ], [
                'first_name.required' => 'Please add your first name.',
                'phone.required' => 'Please add your WhatsApp number.',
                'consent.accepted' => 'Please tick the box to agree before connecting.',
            ]);

            if ($validator->fails()) {
                return response()->view('portal.splash', $context + [
                    'errors' => $validator->errors(),
                    'old' => $request->only('first_name', 'phone', 'email', 'marketing_opt_in', 'birthday_month', 'birthday_day'),
                ], 422);
            }

            $guestData = $validator->validated();
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
                'old' => $request->only('first_name', 'phone', 'email', 'consent', 'marketing_opt_in', 'birthday_month', 'birthday_day'),
            ], 503);
        }

        // A failed controller request must not create a guest or a successful visit.
        if ($guestData !== null) {
            $this->guests->capture($context['property'], $guestData, $context['consentText'], $context['deviceMac']);
        } elseif ($context['returningGuest']) {
            $this->guests->recordVisit($context['returningGuest'], $context['deviceMac']);
        }

        // Best-effort: mark the AP as recently seen.
        if (! empty($data['ap'])) {
            AccessPoint::where('mac_address', $this->unifi->normalizeMac($data['ap']))
                ->update(['last_seen' => now(), 'status' => 'online']);
        }

        // Business customers land on the business's homepage (menu, offers).
        if ($context['property']?->host?->isBusiness()) {
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

        return [
            'params' => $params,
            'property' => $property,
            'propertyName' => $propertyName,
            'logoUrl' => SplashDesign::imageUrl($property?->logo_path),
            'splashImageUrl' => SplashDesign::imageUrl($property?->splash_image_path),
            'design' => SplashDesign::forProperty($property),
            'canConnect' => $property && is_string($params['id']) && (bool) preg_match('/^(?:[0-9a-fA-F]{12}|(?:[0-9a-fA-F]{2}[:-]){5}[0-9a-fA-F]{2})$/', $params['id']),
            'deviceMac' => $deviceMac,
            'returningGuest' => $property ? $this->guests->findReturning($property, $deviceMac) : null,
            'consentText' => static::consentText($propertyName),
            'isBusiness' => (bool) $property?->host?->isBusiness(),
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
