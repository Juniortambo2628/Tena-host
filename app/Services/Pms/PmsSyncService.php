<?php

namespace App\Services\Pms;

use App\Models\Guest;
use App\Models\Property;

class PmsSyncService
{
    /**
     * Sync reservations from a PMS payload into guest records.
     */
    public function sync(Property $property, string $provider, array $payload): array
    {
        $driver = PmsFactory::driver($provider);
        $reservations = $driver->parseReservations($property, $payload);

        $created = 0;
        $updated = 0;

        foreach ($reservations as $reservation) {
            $guest = Guest::updateOrCreate(
                [
                    'property_id' => $property->id,
                    'external_id' => $reservation['external_id'],
                ],
                [
                    'first_name' => $reservation['first_name'],
                    'last_name' => $reservation['last_name'],
                    'email' => $reservation['email'],
                    'phone' => $reservation['phone'] ?? null,
                    'source' => $reservation['source'] ?? $provider,
                    'check_in' => $reservation['check_in'] ?? null,
                    'check_out' => $reservation['check_out'] ?? null,
                ]
            );

            if ($guest->wasRecentlyCreated) {
                $created++;
            } else {
                $updated++;
            }
        }

        $property->update([
            'pms_connection_status' => 'connected',
            'pms_last_sync_at' => now(),
        ]);

        return [
            'created' => $created,
            'updated' => $updated,
            'total' => count($reservations),
        ];
    }
}
