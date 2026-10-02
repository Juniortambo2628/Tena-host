<?php

namespace App\Http\Controllers;

use App\Models\AccessPoint;
use App\Services\Unifi\UnifiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Public external captive portal for Ubiquiti UniFi.
 *
 * The UniFi AP/controller redirects a connecting device to GET /portal with
 * query params describing the client, and the device taps "Connect" to POST
 * /portal/connect, which authorizes it against the controller.
 */
class WifiPortalController extends Controller
{
    public function __construct(protected UnifiService $unifi)
    {
    }

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

        return view('portal.connected', $this->context($request));
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
            $property = AccessPoint::with('property:id,name')
                ->where('mac_address', $this->unifi->normalizeMac($params['ap']))
                ->first()?->property;
        }

        return [
            'params' => $params,
            'propertyName' => $property?->name ?? config('app.name'),
        ];
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
