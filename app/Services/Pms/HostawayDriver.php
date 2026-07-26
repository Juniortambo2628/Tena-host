<?php

namespace App\Services\Pms;

use App\Models\Property;

class HostawayDriver implements PmsDriver
{
    public function parseReservations(Property $property, array $payload): array
    {
        $reservations = [];
        $items = $payload['reservations'] ?? $payload['data'] ?? [$payload];

        foreach ($items as $reservation) {
            $guest = $reservation['guest'] ?? $reservation;

            $reservations[] = [
                'first_name' => $guest['first_name'] ?? $guest['firstName'] ?? 'Guest',
                'last_name' => $guest['last_name'] ?? $guest['lastName'] ?? '',
                'email' => $guest['email'] ?? null,
                'phone' => $guest['phone'] ?? null,
                'check_in' => $reservation['arrivalDate'] ?? $reservation['check_in'] ?? null,
                'check_out' => $reservation['departureDate'] ?? $reservation['check_out'] ?? null,
                'source' => 'Hostaway',
                'external_id' => (string) ($reservation['id'] ?? $reservation['reservation_id'] ?? uniqid()),
            ];
        }

        return $reservations;
    }

    public function verifyConnection(Property $property): bool
    {
        return ! empty($property->pms_integration_type);
    }
}
