<?php

namespace App\Services\Unifi;

use App\Models\Setting;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Minimal client for authorizing guest devices against a Ubiquiti UniFi
 * controller, used by the external captive portal.
 *
 * Supports both classic self-hosted controllers (login at /api/login, API at
 * /api/s/{site}/...) and UniFi OS consoles such as the UDM/UDM-Pro/CloudKey
 * Gen2+ (login at /api/auth/login, API proxied under /proxy/network/...).
 */
class UnifiService
{
    protected CookieJar $cookies;

    protected ?string $csrfToken = null;

    protected bool $loggedIn = false;

    public function __construct(
        protected ?string $baseUrl = null,
        protected ?string $username = null,
        protected ?string $password = null,
        protected string $site = 'default',
        protected bool $isUnifiOs = true,
        protected bool $verifySsl = false,
        protected int $timeout = 10,
    ) {
        $this->baseUrl = rtrim($baseUrl ?? (string) config('unifi.base_url'), '/');
        $this->username ??= config('unifi.username');
        $this->password ??= config('unifi.password');
        $this->site = $site ?: config('unifi.site', 'default');
        $this->isUnifiOs = $isUnifiOs;
        $this->verifySsl = $verifySsl;
        $this->timeout = $timeout;
        $this->cookies = new CookieJar;
    }

    public static function fromConfig(): self
    {
        return new self(
            baseUrl: config('unifi.base_url'),
            username: config('unifi.username'),
            password: config('unifi.password'),
            site: config('unifi.site', 'default'),
            isUnifiOs: (bool) config('unifi.is_unifi_os', true),
            verifySsl: (bool) config('unifi.verify_ssl', false),
            timeout: (int) config('unifi.timeout', 10),
        );
    }

    /**
     * Build from admin-managed settings (database), falling back to .env/config.
     * This is what the live portal uses, so hosts can update it in the admin UI
     * without editing .env or clearing caches.
     */
    public static function fromSettings(): self
    {
        return new self(
            baseUrl: Setting::getValue('unifi_base_url') ?: config('unifi.base_url'),
            username: Setting::getValue('unifi_username') ?: config('unifi.username'),
            password: self::storedPassword(),
            site: Setting::getValue('unifi_site') ?: config('unifi.site', 'default'),
            isUnifiOs: (bool) Setting::getValue('unifi_is_os', config('unifi.is_unifi_os', true)),
            verifySsl: (bool) Setting::getValue('unifi_verify_ssl', config('unifi.verify_ssl', false)),
            timeout: (int) Setting::getValue('unifi_timeout', config('unifi.timeout', 10)),
        );
    }

    /**
     * The decrypted controller password from settings, or the config fallback.
     */
    public static function storedPassword(): ?string
    {
        $stored = Setting::getValue('unifi_password');

        if (filled($stored)) {
            try {
                return Crypt::decryptString($stored);
            } catch (\Throwable) {
                return null;
            }
        }

        return config('unifi.password');
    }

    /**
     * Verify we can log in and reach the configured site.
     *
     * @return array{ok: bool, message: string}
     */
    public function testConnection(): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'message' => 'Controller URL, username and password are all required.'];
        }

        try {
            $this->login();

            $response = $this->client()->get($this->apiPath('self'));

            if ($response->successful()) {
                return ['ok' => true, 'message' => 'Connected to the controller and reached site "'.$this->site.'".'];
            }

            return [
                'ok' => false,
                'message' => 'Logged in, but could not read site "'.$this->site.'" (HTTP '.$response->status().'). Check the Site value.',
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    public function isConfigured(): bool
    {
        return filled($this->baseUrl) && filled($this->username) && filled($this->password);
    }

    /**
     * Authorize a guest device so it gets internet access.
     *
     * @param  string  $clientMac  The guest device MAC (UniFi "id" param).
     * @param  string|null  $apMac  The AP the client is on (UniFi "ap" param).
     * @param  int|null  $minutes  Override the configured authorization window.
     */
    public function authorizeGuest(string $clientMac, ?string $apMac = null, ?int $minutes = null): bool
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('UniFi controller is not configured. Set UNIFI_BASE_URL, UNIFI_USERNAME and UNIFI_PASSWORD.');
        }

        $this->login();

        $payload = [
            'cmd' => 'authorize-guest',
            'mac' => $this->normalizeMac($clientMac),
            'minutes' => $minutes ?? (int) Setting::getValue('unifi_auth_minutes', config('unifi.auth_minutes', 1440)),
        ];

        if ($apMac) {
            $payload['ap_mac'] = $this->normalizeMac($apMac);
        }

        $response = $this->client()->post($this->apiPath('cmd/stamgr'), $payload);

        if (! $response->successful()) {
            Log::warning('UniFi authorize-guest failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'mac' => $payload['mac'],
            ]);

            return false;
        }

        return true;
    }

    /**
     * Every adopted device on the site with its connection state.
     *
     * @return list<array{mac: string, online: bool, last_seen: ?Carbon, clients: int}>
     */
    public function devices(): array
    {
        $this->login();

        $response = $this->client()->get($this->apiPath('stat/device'));

        if (! $response->successful()) {
            throw new RuntimeException('UniFi device list failed (HTTP '.$response->status().').');
        }

        return collect($response->json('data', []))->map(fn (array $d) => [
            'mac' => $this->normalizeMac($d['mac'] ?? ''),
            'online' => (int) ($d['state'] ?? 0) === 1, // 1 = connected
            'last_seen' => isset($d['last_seen']) ? Carbon::createFromTimestamp($d['last_seen']) : null,
            'clients' => (int) ($d['num_sta'] ?? 0),
        ])->all();
    }

    /**
     * Authenticate against the controller and capture session cookies.
     */
    protected function login(): void
    {
        if ($this->loggedIn) {
            return;
        }

        $path = $this->isUnifiOs ? '/api/auth/login' : '/api/login';

        $response = $this->client()->post($path, [
            'username' => $this->username,
            'password' => $this->password,
            'remember' => false,
        ]);

        if (! $response->successful()) {
            Log::error('UniFi login failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new RuntimeException('Unable to log in to the UniFi controller (HTTP '.$response->status().').');
        }

        // UniFi OS returns a CSRF token that must be echoed on mutating calls.
        $this->csrfToken = $response->header('X-CSRF-Token')
            ?: $response->header('x-csrf-token')
            ?: $this->csrfToken;

        $this->loggedIn = true;
    }

    /**
     * Build the site-scoped API path, honoring the UniFi OS proxy prefix.
     */
    protected function apiPath(string $suffix): string
    {
        $prefix = $this->isUnifiOs ? '/proxy/network' : '';

        return $prefix.'/api/s/'.$this->site.'/'.ltrim($suffix, '/');
    }

    protected function client(): PendingRequest
    {
        $request = Http::baseUrl($this->baseUrl)
            ->timeout($this->timeout)
            ->asJson()
            ->acceptJson()
            ->withOptions([
                'verify' => $this->verifySsl,
                'cookies' => $this->cookies,
            ]);

        if ($this->csrfToken) {
            $request = $request->withHeaders(['X-CSRF-Token' => $this->csrfToken]);
        }

        return $request;
    }

    /**
     * Normalize a MAC to lowercase, colon-separated form (aa:bb:cc:dd:ee:ff).
     */
    public function normalizeMac(string $mac): string
    {
        $hex = strtolower(preg_replace('/[^0-9a-fA-F]/', '', $mac));

        if (strlen($hex) === 12) {
            return implode(':', str_split($hex, 2));
        }

        // Fall back to the trimmed original if it isn't a clean 12-hex string.
        return strtolower(trim($mac));
    }
}
