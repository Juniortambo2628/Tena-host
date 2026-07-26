<?php

namespace App\Services\Pms;

use App\Models\Property;

class CloudbedsDriver implements PmsDriver
{
    public function parseReservations(Property $property, array $payload): array
    {
        $reservations = [];
        $items = $payload['reservations'] ?? [$payload];

        foreach ($items as $reservation) {
            $guest = $reservation['guest'] ?? $reservation;

            $reservations[] = [
                'first_name' => $guest['first_name'] ?? $guest['firstName'] ?? 'Guest',
                'last_name' => $guest['last_name'] ?? $guest['lastName'] ?? '',
                'email' => $guest['email'] ?? null,
                'phone' => $guest['phone'] ?? null,
                'check_in' => $reservation['checkin'] ?? $reservation['check_in'] ?? null,
                'check_out' => $reservation['checkout'] ?? $reservation['check_out'] ?? null,
                'source' => 'Cloudbeds',
                'external_id' => (string) ($reservation['reservation_id'] ?? $reservation['id'] ?? uniqid()),
            ];
        }

        return $reservations;
    }

    public function verifyConnection(Property $property): bool
    {
        return ! empty($property->pms_integration_type);
    }
}
