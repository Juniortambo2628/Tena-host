<?php

namespace App\Services;

use App\Models\AccessPoint;
use App\Models\Guest;
use App\Models\Property;
use App\Models\Setting;
use App\Models\User;
use App\Models\WifiEvent;
use App\Services\Unifi\UnifiService;
use Illuminate\Support\Facades\Log;

/**
 * Outage and occupancy alerts for hosts (alerts:check, every 5 minutes).
 *
 * Outage: an access point that has been seen before but not for
 * OUTAGE_MINUTES is down. The host hears once when it drops and once when
 * it's back. AP status comes from the UniFi controller when one is set up.
 *
 * Occupancy: rental hosts set a guest limit per property (occupancy
 * threshold). More distinct guests connecting within OCCUPANCY_HOURS than
 * that is an early warning for parties or extra guests, sent at most once
 * a day.
 */
class PropertyMonitorService
{
    public const OUTAGE_MINUTES = 10;

    public const OCCUPANCY_HOURS = 12;

    public function __construct(
        protected HostAlertService $alerts,
        protected UnifiService $unifi,
    ) {}

    /**
     * @return array{synced: int, outages: int, recovered: int, occupancy: int}
     */
    public function check(): array
    {
        return [
            'synced' => $this->syncFromUnifi(),
            ...$this->checkOutages(),
            'occupancy' => $this->checkOccupancy(),
        ];
    }

    /**
     * Refresh AP status from the controller. Returns how many APs matched.
     */
    public function syncFromUnifi(): int
    {
        if (! $this->unifi->isConfigured()) {
            return 0;
        }

        try {
            $devices = collect($this->unifi->devices())->keyBy('mac');
        } catch (\Throwable $e) {
            Log::warning('Outage check could not reach UniFi: '.$e->getMessage());
            // Logged at most once an hour while it keeps failing (the check runs every 5 minutes).
            if (! WifiEvent::where('type', 'device_sync_failed')->where('created_at', '>=', now()->subHour())->exists()) {
                WifiEvent::record('device_sync_failed', 'The access point check couldn\'t reach the controller: '.UnifiService::shortError($e->getMessage()), [], 'error');
            }

            return 0;
        }

        Setting::setValue('unifi_last_sync_at', now()->toIso8601String(), 'unifi');

        $synced = 0;
        AccessPoint::with('property:id,name')->whereIn('mac_address', $devices->keys())->each(function (AccessPoint $ap) use ($devices, &$synced) {
            $device = $devices[$ap->mac_address];
            $status = $device['online'] ? 'online' : 'offline';
            if ($ap->status !== $status && in_array($ap->status, ['online', 'offline'], true)) {
                WifiEvent::record($status === 'online' ? 'ap_online' : 'ap_offline',
                    "{$ap->name} at {$ap->property?->name} is ".($status === 'online' ? 'back online.' : 'offline.'),
                    ['ap' => $ap->mac_address], $status === 'online' ? 'info' : 'warning', $ap->property_id);
            }
            $ap->update([
                'status' => $status,
                'last_seen' => $device['online'] ? now() : ($device['last_seen'] ?? $ap->last_seen),
                'connected_clients_count' => $device['clients'],
            ]);
            $synced++;
        });

        return $synced;
    }

    /**
     * @return array{outages: int, recovered: int}
     */
    public function checkOutages(): array
    {
        $cutoff = now()->subMinutes(self::OUTAGE_MINUTES);
        $outages = $recovered = 0;

        AccessPoint::with('property.host')->whereHas('property')->whereNotNull('last_seen')->each(function (AccessPoint $ap) use ($cutoff, &$outages, &$recovered) {
            $down = $ap->status === 'offline' || $ap->last_seen->lt($cutoff);
            $place = $ap->property->name;

            if ($down && ! $ap->outage_alerted_at) {
                $this->alerts->send($ap->property, 'outage_alert', 'WiFi is down',
                    "The WiFi at {$place} ({$ap->name}) has been offline since {$ap->last_seen->format('H:i')}. Guests can't connect until it's back.");
                $ap->update(['status' => 'offline', 'outage_alerted_at' => now()]);
                $outages++;
            } elseif (! $down && $ap->outage_alerted_at) {
                $this->alerts->send($ap->property, 'outage_resolved', 'WiFi is back',
                    "The WiFi at {$place} ({$ap->name}) is back online after {$ap->outage_alerted_at->diffForHumans(null, true)}.");
                $ap->update(['outage_alerted_at' => null]);
                $recovered++;
            }
        });

        return ['outages' => $outages, 'recovered' => $recovered];
    }

    public function checkOccupancy(): int
    {
        $alerted = 0;

        Property::with('host')
            ->whereHas('host', fn ($q) => $q->where('account_type', User::ACCOUNT_HOST))
            ->where('occupancy_threshold', '>', 0)
            ->where(fn ($q) => $q->whereNull('occupancy_alerted_at')->orWhere('occupancy_alerted_at', '<', now()->subDay()))
            ->each(function (Property $property) use (&$alerted) {
                $people = Guest::where('property_id', $property->id)
                    ->where('last_connected', '>=', now()->subHours(self::OCCUPANCY_HOURS))
                    ->count();

                if ($people > $property->occupancy_threshold) {
                    $this->alerts->send($property, 'occupancy_alert', 'More guests than expected',
                        "{$people} people have connected to the WiFi at {$property->name} in the last ".self::OCCUPANCY_HOURS." hours. Your limit is {$property->occupancy_threshold}.");
                    $property->update(['occupancy_alerted_at' => now()]);
                    $alerted++;
                }
            });

        return $alerted;
    }
}
