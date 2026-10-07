<?php

namespace App\Http\Controllers;

use App\Models\AccessPoint;
use App\Models\Property;
use App\Services\GuestCaptureService;
use App\Services\Unifi\UnifiService;
use App\Support\Brand;
use App\Support\Phone;
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
     * Authorize the device against the UniFi controller and send it on its way.
     */
    public function connect(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|string|max:64',   // client MAC
            'ap' => 'nullable|string|max:64',    // AP MAC
            't' => 'nullable|string|max:32',
            'ssid' => 'nullable|string|max:64',
            'url' => 'nullable|string|max:2048', // original destination
        ]);

        $context = $this->context($request);

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
            ], [
                'first_name.required' => 'Please add your first name.',
                'phone.required' => 'Please add your WhatsApp number.',
                'consent.accepted' => 'Please tick the box to agree before connecting.',
            ]);

            if ($validator->fails()) {
                return response()->view('portal.splash', $context + [
                    'errors' => $validator->errors(),
                    'old' => $request->only('first_name', 'phone', 'email', 'marketing_opt_in'),
                ], 422);
            }

            $this->guests->capture(
                $context['property'],
                $validator->validated(),
                $context['consentText'],
                $context['deviceMac'],
            );
        } elseif ($context['returningGuest']) {
            $this->guests->recordVisit($context['returningGuest'], $context['deviceMac']);
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
            return redirect()->route('portal.show', $this->passthrough($data) + [
                'error' => 'We could not connect you to the WiFi. Please try again.',
            ]);
        }

        // Best-effort: mark the AP as recently seen.
        if (! empty($data['ap'])) {
            AccessPoint::where('mac_address', $this->unifi->normalizeMac($data['ap']))
                ->update(['last_seen' => now(), 'status' => 'online']);
        }

        // Send the device back to where it was headed, or to a success page.
        $target = $data['url'] ?? null;
        if ($target && preg_match('#^https?://#i', $target)) {
            return redirect()->away($target);
        }

        return view('portal.connected', $context);
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
            'logoUrl' => $property?->logo_path ? asset('storage/'.ltrim($property->logo_path, '/')) : null,
            'deviceMac' => $deviceMac,
            'returningGuest' => $property ? $this->guests->findReturning($property, $deviceMac) : null,
            'consentText' => static::consentText($propertyName),
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
