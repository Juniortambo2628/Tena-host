<?php

namespace App\Services\Unifi;

use App\Models\AccessPoint;
use App\Models\Setting;
use App\Models\WifiEvent;
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
     * The connection test, step by step, for the admin's WiFi setup screen:
     * can we reach the controller, log in, open the site and list its
     * access points? Each failed step says what to check.
     *
     * @return array{ok: bool, message: string, duration_ms: int, devices: ?int, steps: list<array{key: string, label: string, ok: ?bool, detail: string, hint: ?string}>}
     */
    public function diagnose(): array
    {
        $started = microtime(true);
        $steps = [
            'details' => ['label' => 'Controller details', 'ok' => null, 'detail' => '', 'hint' => null],
            'reach' => ['label' => 'Reach the controller', 'ok' => null, 'detail' => '', 'hint' => null],
            'login' => ['label' => 'Log in', 'ok' => null, 'detail' => '', 'hint' => null],
            'site' => ['label' => 'Open the site', 'ok' => null, 'detail' => '', 'hint' => null],
            'devices' => ['label' => 'List access points', 'ok' => null, 'detail' => '', 'hint' => null],
        ];
        $devices = null;
        $fail = function (string $key, string $detail, string $hint) use (&$steps) {
            $steps[$key] = ['ok' => false, 'detail' => $detail, 'hint' => $hint] + $steps[$key];
        };
        $pass = function (string $key, string $detail) use (&$steps) {
            $steps[$key] = ['ok' => true, 'detail' => $detail, 'hint' => null] + $steps[$key];
        };

        if (! $this->isConfigured()) {
            $fail('details', 'Controller URL, username and password are all needed.', 'Fill in the three fields above (a blank password keeps the saved one).');
        } else {
            $pass('details', $this->baseUrl.' · site "'.$this->site.'" · '.($this->isUnifiOs ? 'UniFi OS' : 'classic controller'));

            try {
                $response = $this->client()->get('/');
                $pass('reach', 'The controller answered (HTTP '.$response->status().').');
            } catch (\Throwable $e) {
                $message = $e->getMessage();
                $fail('reach', static::shortError($message), match (true) {
                    str_contains($message, 'SSL') || str_contains($message, 'certificate') => 'The controller uses a certificate this server doesn\'t trust. Turn off "Verify the controller\'s SSL certificate" for a self-signed controller.',
                    str_contains($message, 'timed out') || str_contains($message, 'timeout') => 'No answer in time. Check the address and port (UniFi OS: 443, classic: 8443), that the controller is online, and that its firewall lets this server in.',
                    (bool) preg_match('/status code 5\d\d/', $message) => 'Something in front of the controller (a proxy, tunnel or firewall) answered with an error instead of the controller. Check that the controller is online and that the address points straight at it.',
                    str_contains($message, 'resolve') => 'That address doesn\'t exist. Check the spelling of the controller URL.',
                    default => 'Check the address and port, and that the controller is reachable from the internet (or from this server\'s network).',
                });
            }

            try {
                if ($steps['reach']['ok']) {
                    $response = $this->client()->post($this->isUnifiOs ? '/api/auth/login' : '/api/login', [
                        'username' => $this->username, 'password' => $this->password, 'remember' => false,
                    ]);
                    if ($response->successful()) {
                        $this->csrfToken = $response->header('X-CSRF-Token') ?: $response->header('x-csrf-token') ?: $this->csrfToken;
                        $this->loggedIn = true;
                        $pass('login', 'Logged in as '.$this->username.'.');
                    } else {
                        $fail('login', 'The controller refused the login (HTTP '.$response->status().').', match ($response->status()) {
                            400, 401, 403 => 'Wrong username or password, or the account needs two-factor sign-in. Use a local controller admin without 2FA.',
                            404 => 'The login page wasn\'t found. Switch the controller type (UniFi OS or classic).',
                            429 => 'Too many attempts. Wait a few minutes and test again.',
                            default => 'Check the controller type and that the account is a local admin.',
                        });
                    }
                }

                if ($steps['login']['ok']) {
                    $response = $this->client()->get($this->apiPath('self'));
                    if ($response->successful()) {
                        $pass('site', 'Site "'.$this->site.'" is open.');
                    } else {
                        $fail('site', 'Couldn\'t open site "'.$this->site.'" (HTTP '.$response->status().').', 'Check the site ID: it\'s in the controller\'s address bar (…/manage/site/<id>/…), usually "default".');
                    }
                }

                if ($steps['site']['ok']) {
                    $response = $this->client()->get($this->apiPath('stat/device'));
                    if ($response->successful()) {
                        $devices = count($response->json('data', []));
                        $pass('devices', $devices === 1 ? '1 device on the site.' : "{$devices} devices on the site.");
                    } else {
                        $fail('devices', 'Couldn\'t list devices (HTTP '.$response->status().').', 'The account may lack permission to view devices. Give it the Site Admin role.');
                    }
                }
            } catch (\Throwable $e) {
                // A step that started but never answered (timeout, dropped connection).
                $key = collect($steps)->search(fn ($step) => $step['ok'] === null);
                $fail($key ?: 'devices', static::shortError($e->getMessage()), 'The controller stopped answering part-way. Test again; if it keeps happening, raise the timeout.');
            }
        }

        $ok = collect($steps)->every(fn ($step) => $step['ok'] === true);
        $failed = collect($steps)->first(fn ($step) => $step['ok'] === false);

        return [
            'ok' => $ok,
            'message' => $ok ? 'Connected. Guests can be let online through this controller.' : ($failed['label'] ?? 'Test').': '.($failed['detail'] ?? 'failed'),
            'duration_ms' => (int) round((microtime(true) - $started) * 1000),
            'devices' => $devices,
            'steps' => collect($steps)->map(fn ($step, $key) => ['key' => $key] + $step)->values()->all(),
        ];
    }

    /** The useful part of a cURL error ("cURL error 28: ... timed out after 10001 ms" -> "Timed out after 10001 ms"). */
    public static function shortError(string $message): string
    {
        $message = preg_replace('/^cURL error \d+:\s*/', '', $message);
        $message = preg_replace('/\s*\(see https?:\/\/[^)]+\).*$/', '', $message);

        return ucfirst(mb_substr(trim($message), 0, 200));
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

        $propertyId = isset($payload['ap_mac']) ? AccessPoint::where('mac_address', $payload['ap_mac'])->value('property_id') : null;

        if (! $response->successful() || $response->json('meta.rc') !== 'ok') {
            Log::warning('UniFi authorize-guest failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'mac' => $payload['mac'],
            ]);
            WifiEvent::record('guest_refused', 'The controller didn\'t let a device online (HTTP '.$response->status().', '.($response->json('meta.msg') ?: ($response->json('meta.rc') ?: 'no reason given')).').', [
                'device' => $payload['mac'], 'ap' => $payload['ap_mac'] ?? null, 'status' => $response->status(),
            ], 'error', $propertyId);

            return false;
        }

        WifiEvent::record('guest_authorized', 'A device was let online for '.$payload['minutes'].' minutes.', [
            'device' => $payload['mac'], 'ap' => $payload['ap_mac'] ?? null,
        ], 'info', $propertyId);

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
            WifiEvent::record('controller_login_failed', 'TenaFi couldn\'t log in to the controller (HTTP '.$response->status().').', ['status' => $response->status()], 'error');
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
