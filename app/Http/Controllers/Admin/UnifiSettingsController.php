<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccessPoint;
use App\Models\Setting;
use App\Models\WifiEvent;
use App\Services\PropertyMonitorService;
use App\Services\Unifi\UnifiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Inertia\Inertia;

class UnifiSettingsController extends Controller
{
    private const GROUP = 'unifi';

    /** The connection fields shared by save and test. */
    private const RULES = [
        'mode' => 'nullable|in:local,cloud',
        'api_key' => 'nullable|string|max:512',
        'console_id' => 'nullable|string|max:255',
        // Only used by the direct connection; through the cloud it's hidden, so
        // a half-typed address left in it mustn't block saving or testing.
        'base_url' => 'exclude_if:mode,cloud|nullable|url|max:255',
        'username' => 'nullable|string|max:255',
        'password' => 'nullable|string|max:255',
        'site' => 'nullable|string|max:64',
        'is_os' => 'boolean',
        'verify_ssl' => 'boolean',
        'timeout' => 'integer|min:1|max:120',
    ];

    /**
     * Show the UniFi captive-portal settings form.
     */
    public function edit()
    {
        return Inertia::render('Admin/Settings/Unifi', [
            'settings' => [
                'mode' => Setting::getValue('unifi_mode') === 'cloud' ? 'cloud' : 'local',
                'console_id' => Setting::getValue('unifi_console_id', ''),
                // Like the password: only whether a key is saved, never the key.
                'has_api_key' => filled(Setting::getValue('unifi_api_key')),
                'base_url' => Setting::getValue('unifi_base_url', config('unifi.base_url')),
                'username' => Setting::getValue('unifi_username', config('unifi.username')),
                'site' => Setting::getValue('unifi_site', config('unifi.site', 'default')),
                'is_os' => (bool) Setting::getValue('unifi_is_os', config('unifi.is_unifi_os', true)),
                'verify_ssl' => (bool) Setting::getValue('unifi_verify_ssl', config('unifi.verify_ssl', false)),
                'auth_minutes' => (int) Setting::getValue('unifi_auth_minutes', config('unifi.auth_minutes', 1440)),
                'timeout' => (int) Setting::getValue('unifi_timeout', config('unifi.timeout', 10)),
                // Never send the password to the browser; just whether one is stored.
                'has_password' => filled(Setting::getValue('unifi_password')) || filled(config('unifi.password')),
            ],
            'portal_url' => url('/portal'),
            // The controller's walled garden must let guests reach this host before they're online.
            'portal_host' => parse_url(url('/'), PHP_URL_HOST),
            'status' => $this->status(),
            'accessPoints' => AccessPoint::with('property:id,name')->orderBy('property_id')->get()
                ->map(fn (AccessPoint $ap) => [
                    'id' => $ap->id,
                    'name' => $ap->name,
                    'mac' => $ap->mac_address,
                    'property' => $ap->property?->name,
                    'status' => $ap->last_seen ? ($ap->status ?: 'unknown') : 'never_seen',
                    'clients' => (int) $ap->connected_clients_count,
                    'last_seen' => $ap->last_seen?->toIso8601String(),
                ]),
            'events' => WifiEvent::with('property:id,name')->latest('created_at')->latest('id')->limit(300)->get()
                ->map(fn (WifiEvent $e) => [
                    'id' => $e->id,
                    'level' => $e->level,
                    'type' => $e->type,
                    'label' => WifiEvent::TYPES[$e->type] ?? $e->type,
                    'message' => $e->message,
                    'property' => $e->property?->name,
                    'device' => $e->context['device'] ?? null,
                    'at' => $e->created_at?->toIso8601String(),
                ]),
            'eventTypes' => WifiEvent::TYPES,
        ]);
    }

    /**
     * Health at a glance: is a controller set up, how did the last test go,
     * when were access points last checked, and how guest sign-ins went in
     * the last 24 hours.
     */
    private function status(): array
    {
        $since = now()->subDay();
        $lastTest = json_decode((string) Setting::getValue('unifi_last_test', ''), true);
        $aps = AccessPoint::query();

        return [
            'configured' => UnifiService::fromSettings()->isConfigured(),
            'lastTest' => is_array($lastTest) ? $lastTest : null,
            'lastSyncAt' => Setting::getValue('unifi_last_sync_at') ?: null,
            'lastError' => WifiEvent::where('level', 'error')->latest('created_at')->latest('id')->first(['message', 'created_at'])?->only(['message', 'created_at']),
            'authorized24h' => WifiEvent::where('type', 'guest_authorized')->where('created_at', '>=', $since)->count(),
            'refused24h' => WifiEvent::where('type', 'guest_refused')->where('created_at', '>=', $since)->count(),
            'aps' => [
                'total' => (clone $aps)->count(),
                'online' => (clone $aps)->whereNotNull('last_seen')->where('status', 'online')->count(),
                'offline' => (clone $aps)->whereNotNull('last_seen')->where('status', 'offline')->count(),
                'neverSeen' => (clone $aps)->whereNull('last_seen')->count(),
            ],
        ];
    }

    /** "Check access points now": the same refresh the 5-minute check runs. */
    public function sync(PropertyMonitorService $monitor)
    {
        if (! UnifiService::fromSettings()->isConfigured()) {
            return back()->with('error', 'Connect a controller first.');
        }

        $before = WifiEvent::where('type', 'device_sync_failed')->max('id');
        $synced = $monitor->syncFromUnifi();

        if (WifiEvent::where('type', 'device_sync_failed')->max('id') !== $before) {
            return back()->with('error', 'Couldn’t reach the controller. See Logs for the reason.');
        }

        return back()->with('success', $synced === 1 ? '1 access point checked.' : "{$synced} access points checked.");
    }

    /**
     * Persist the UniFi settings.
     */
    public function update(Request $request)
    {
        $validated = $request->validate(self::RULES + [
            'auth_minutes' => 'integer|min:1|max:525600',
        ]);

        $mode = ($validated['mode'] ?? 'local') === 'cloud' ? 'cloud' : 'local';
        if ($mode === 'cloud' && blank($validated['api_key'] ?? null) && blank(Setting::getValue('unifi_api_key'))) {
            return back()->withErrors(['api_key' => 'Paste the API key from unifi.ui.com → Settings → API Keys.']);
        }

        Setting::setValue('unifi_mode', $mode, self::GROUP);
        Setting::setValue('unifi_console_id', trim((string) ($validated['console_id'] ?? '')), self::GROUP);
        if (filled($validated['api_key'] ?? null)) {
            Setting::setValue('unifi_api_key', Crypt::encryptString(trim($validated['api_key'])), self::GROUP);
        }

        if (array_key_exists('base_url', $validated)) {
            Setting::setValue('unifi_base_url', rtrim((string) $validated['base_url'], '/'), self::GROUP);
        }
        Setting::setValue('unifi_username', (string) ($validated['username'] ?? ''), self::GROUP);
        Setting::setValue('unifi_site', $validated['site'] ?: 'default', self::GROUP);
        Setting::setValue('unifi_is_os', $validated['is_os'] ? '1' : '0', self::GROUP, 'boolean');
        Setting::setValue('unifi_verify_ssl', $validated['verify_ssl'] ? '1' : '0', self::GROUP, 'boolean');
        Setting::setValue('unifi_auth_minutes', (string) $validated['auth_minutes'], self::GROUP, 'integer');
        Setting::setValue('unifi_timeout', (string) $validated['timeout'], self::GROUP, 'integer');

        // Only overwrite the password when a new one is supplied; store encrypted.
        if (filled($validated['password'] ?? null)) {
            Setting::setValue('unifi_password', Crypt::encryptString($validated['password']), self::GROUP);
        }

        $notes = array_filter([
            $mode === 'cloud' ? 'through UniFi\'s cloud' : null,
            filled($validated['password'] ?? null) ? 'new password' : null,
            filled($validated['api_key'] ?? null) ? 'new API key' : null,
        ]);
        WifiEvent::record('settings_saved', 'Controller settings saved'.($notes ? ' ('.implode(', ', $notes).').' : '.'), ['by' => $request->user()?->email]);

        return back()->with('success', 'UniFi settings saved.');
    }

    /**
     * Test the connection using the posted values (falling back to stored ones
     * for a blank password), without saving.
     */
    public function test(Request $request)
    {
        $validated = $request->validate(self::RULES);

        $service = ($validated['mode'] ?? 'local') === 'cloud' ? new UnifiService(
            site: ($validated['site'] ?? null) ?: Setting::getValue('unifi_site', config('unifi.site', 'default')),
            timeout: max(15, (int) ($validated['timeout'] ?? 15)),
            apiKey: filled($validated['api_key'] ?? null) ? trim($validated['api_key']) : UnifiService::storedApiKey(),
            consoleId: trim((string) ($validated['console_id'] ?? '')) ?: null,
        ) : new UnifiService(
            baseUrl: ($validated['base_url'] ?? null) ?: Setting::getValue('unifi_base_url', config('unifi.base_url')),
            username: ($validated['username'] ?? null) ?: Setting::getValue('unifi_username', config('unifi.username')),
            password: filled($validated['password'] ?? null)
                ? $validated['password']
                : UnifiService::storedPassword(),
            site: ($validated['site'] ?? null) ?: Setting::getValue('unifi_site', config('unifi.site', 'default')),
            isUnifiOs: (bool) ($validated['is_os'] ?? true),
            verifySsl: (bool) ($validated['verify_ssl'] ?? false),
            timeout: (int) ($validated['timeout'] ?? 10),
        );

        if (! $service->usesCloud() && ($validated['mode'] ?? 'local') === 'cloud') {
            return response()->json(['ok' => false, 'message' => 'Paste the API key first.', 'steps' => []]);
        }

        $result = $service->diagnose();
        Setting::setValue('unifi_last_test', json_encode(['ok' => $result['ok'], 'message' => $result['message'], 'at' => now()->toIso8601String()]), self::GROUP);
        WifiEvent::record('connection_test', ($result['ok'] ? 'Test passed: ' : 'Test failed: ').$result['message'], ['ms' => $result['duration_ms'], 'devices' => $result['devices']], $result['ok'] ? 'info' : 'error');

        return response()->json($result);
    }
}
