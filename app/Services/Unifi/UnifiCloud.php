<?php

namespace App\Services\Unifi;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use RuntimeException;

/**
 * Talks to a UniFi console through UniFi's cloud (api.ui.com) with an API key
 * made at unifi.ui.com -> Settings -> API Keys. Calls go to the official
 * Network API via the Site Manager "Cloud Connector", which reaches the
 * console over the connection it already keeps open to UniFi's cloud, so the
 * console needs no public address, port forward or VPN (it can sit behind the
 * ISP's router).
 *
 * Network API: https://developer.ui.com/network/ (sites, devices, clients and
 * the AUTHORIZE_GUEST_ACCESS client action).
 */
class UnifiCloud
{
    public const BASE_URL = 'https://api.ui.com';

    /** The console's site UUID, looked up once from the site name or reference. */
    protected ?string $siteId = null;

    public function __construct(
        protected string $apiKey,
        protected ?string $consoleId,
        protected string $site = 'default',
        protected int $timeout = 15,
    ) {}

    /**
     * The console ID to use: the one given, or the only console on the account.
     *
     * @return list<array{id: string, name: string}>
     */
    public function consoles(): array
    {
        $response = $this->client()->get('/v1/hosts');
        $this->ensure($response, 'list the consoles on this API key');

        return collect($response->json('data', []))
            ->filter(fn ($h) => ! isset($h['type']) || $h['type'] === 'console')
            ->map(fn ($h) => [
                'id' => (string) ($h['id'] ?? ''),
                'name' => (string) ($h['reportedState']['name'] ?? $h['reportedState']['hostname'] ?? $h['hardwareId'] ?? 'UniFi console'),
            ])
            ->filter(fn ($h) => $h['id'] !== '')
            ->values()->all();
    }

    public function consoleId(): string
    {
        if (filled($this->consoleId)) {
            return $this->consoleId;
        }

        $consoles = $this->consoles();
        if (count($consoles) !== 1) {
            throw new RuntimeException(count($consoles) === 0
                ? 'No UniFi console is linked to this API key.'
                : 'This API key reaches '.count($consoles).' consoles. Enter the console ID of the one to use.');
        }

        return $this->consoleId = $consoles[0]['id'];
    }

    /**
     * The site's UUID, matched by its old-style ID ("default") or its name.
     *
     * @return array{id: string, name: string}
     */
    public function site(): array
    {
        $response = $this->network()->get('/v1/sites', ['limit' => 200]);
        $this->ensure($response, 'open the console\'s sites');

        $sites = collect($response->json('data', []));
        $wanted = strtolower(trim($this->site));
        $site = $sites->first(fn ($s) => strtolower((string) ($s['internalReference'] ?? '')) === $wanted || strtolower((string) ($s['name'] ?? '')) === $wanted || ($s['id'] ?? null) === $this->site)
            ?? ($sites->count() === 1 ? $sites->first() : null);

        if (! $site) {
            throw new RuntimeException('Site "'.$this->site.'" isn\'t on this console. It has: '.($sites->pluck('name')->filter()->implode(', ') ?: 'no sites').'.');
        }

        $this->siteId = $site['id'];

        return ['id' => $site['id'], 'name' => (string) ($site['name'] ?? $site['internalReference'] ?? $site['id'])];
    }

    /**
     * Let a connected guest device online.
     *
     * @return array{ok: bool, status: int, reason: ?string}
     */
    public function authorizeGuest(string $mac, int $minutes): array
    {
        $client = $this->findClient($mac);

        // A device that has only just joined can take a moment to show up.
        if (! $client) {
            Sleep::for(2)->seconds();
            $client = $this->findClient($mac);
        }

        if (! $client) {
            return ['ok' => false, 'status' => 404, 'reason' => 'the device isn\'t connected to this site'];
        }

        $response = $this->network()->post('/v1/sites/'.$this->siteId().'/clients/'.$client['id'].'/actions', [
            'action' => 'AUTHORIZE_GUEST_ACCESS',
            'timeLimitMinutes' => max(1, min($minutes, 1000000)),
        ]);

        return [
            'ok' => $response->successful(),
            'status' => $response->status(),
            'reason' => $response->successful() ? null : $this->reason($response),
        ];
    }

    /**
     * Every adopted device on the site, with how many clients each has.
     *
     * @return list<array{mac: string, online: bool, last_seen: null, clients: int}>
     */
    public function devices(): array
    {
        $response = $this->network()->get('/v1/sites/'.$this->siteId().'/devices', ['limit' => 200]);
        $this->ensure($response, 'list the site\'s devices');

        $clients = $this->network()->get('/v1/sites/'.$this->siteId().'/clients', ['limit' => 200]);
        $perDevice = $clients->successful()
            ? collect($clients->json('data', []))->countBy(fn ($c) => $c['uplinkDeviceId'] ?? '')
            : collect();

        return collect($response->json('data', []))->map(fn ($d) => [
            'mac' => strtolower((string) ($d['macAddress'] ?? '')),
            'online' => ($d['state'] ?? null) === 'ONLINE',
            'last_seen' => null,
            'clients' => (int) ($perDevice[$d['id'] ?? ''] ?? 0),
        ])->all();
    }

    /** @return array{id: string}|null */
    protected function findClient(string $mac): ?array
    {
        $response = $this->network()->get('/v1/sites/'.$this->siteId().'/clients', [
            'filter' => "macAddress.eq('".str_replace("'", '', $mac)."')",
        ]);
        $this->ensure($response, 'look up the device');

        $client = collect($response->json('data', []))
            ->first(fn ($c) => strtolower((string) ($c['macAddress'] ?? '')) === $mac);

        return $client && filled($client['id'] ?? null) ? ['id' => $client['id']] : null;
    }

    protected function siteId(): string
    {
        return $this->siteId ?? $this->site()['id'];
    }

    /** Requests to the console's Network API, through the Cloud Connector. */
    protected function network(): PendingRequest
    {
        return $this->client()->baseUrl(self::BASE_URL.'/v1/connector/consoles/'.rawurlencode($this->consoleId()).'/proxy/network/integration');
    }

    protected function client(): PendingRequest
    {
        return Http::baseUrl(self::BASE_URL)
            ->timeout($this->timeout)
            ->acceptJson()
            ->asJson()
            ->withHeaders(['X-API-Key' => $this->apiKey]);
    }

    protected function ensure(Response $response, string $what): void
    {
        if (! $response->successful()) {
            throw new UnifiCloudException('Couldn\'t '.$what.' (HTTP '.$response->status().': '.$this->reason($response).').', $response->status());
        }
    }

    protected function reason(Response $response): string
    {
        return (string) ($response->json('message') ?: $response->json('code') ?: $response->json('statusName') ?: 'no reason given');
    }
}
