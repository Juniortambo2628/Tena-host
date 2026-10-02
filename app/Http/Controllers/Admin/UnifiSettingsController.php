<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\Unifi\UnifiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Inertia\Inertia;

class UnifiSettingsController extends Controller
{
    private const GROUP = 'unifi';

    /**
     * Show the UniFi captive-portal settings form.
     */
    public function edit()
    {
        return Inertia::render('Admin/Settings/Unifi', [
            'settings' => [
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
        ]);
    }

    /**
     * Persist the UniFi settings.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'base_url' => 'nullable|url|max:255',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:255',
            'site' => 'nullable|string|max:64',
            'is_os' => 'boolean',
            'verify_ssl' => 'boolean',
            'auth_minutes' => 'integer|min:1|max:525600',
            'timeout' => 'integer|min:1|max:120',
        ]);

        Setting::setValue('unifi_base_url', rtrim((string) $validated['base_url'], '/'), self::GROUP);
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

        return back()->with('success', 'UniFi settings saved.');
    }

    /**
     * Test the connection using the posted values (falling back to stored ones
     * for a blank password), without saving.
     */
    public function test(Request $request)
    {
        $validated = $request->validate([
            'base_url' => 'nullable|url|max:255',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:255',
            'site' => 'nullable|string|max:64',
            'is_os' => 'boolean',
            'verify_ssl' => 'boolean',
            'timeout' => 'integer|min:1|max:120',
        ]);

        $service = new UnifiService(
            baseUrl: $validated['base_url'] ?: Setting::getValue('unifi_base_url', config('unifi.base_url')),
            username: $validated['username'] ?: Setting::getValue('unifi_username', config('unifi.username')),
            password: filled($validated['password'] ?? null)
                ? $validated['password']
                : UnifiService::storedPassword(),
            site: $validated['site'] ?: Setting::getValue('unifi_site', config('unifi.site', 'default')),
            isUnifiOs: (bool) ($validated['is_os'] ?? true),
            verifySsl: (bool) ($validated['verify_ssl'] ?? false),
            timeout: (int) ($validated['timeout'] ?? 10),
        );

        return response()->json($service->testConnection());
    }
}
