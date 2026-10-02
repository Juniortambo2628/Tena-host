<?php

namespace App\Services\Unifi;

use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Http\Client\PendingRequest;
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
            'minutes' => $minutes ?? (int) config('unifi.auth_minutes', 1440),
        ];

        if ($apMac) {
            $payload['ap_mac'] = $this->normalizeMac($apMac);
        }

        $response = $this->client()->post($this->apiPath("cmd/stamgr"), $payload);

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
