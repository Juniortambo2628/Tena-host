<?php

namespace App\Services\Pms;

use App\Models\Property;

class Beds24Driver implements PmsDriver
{
    public function parseReservations(Property $property, array $payload): array
    {
        $bookings = $payload['bookings'] ?? [$payload];
        $reservations = [];

        foreach ($bookings as $booking) {
            $guest = $booking['guest'] ?? $booking;

            $reservations[] = [
                'first_name' => $guest['firstName'] ?? $guest['first_name'] ?? 'Guest',
                'last_name' => $guest['lastName'] ?? $guest['last_name'] ?? '',
                'email' => $guest['email'] ?? null,
                'phone' => $guest['phone'] ?? null,
                'check_in' => $booking['arrival'] ?? $booking['check_in'] ?? null,
                'check_out' => $booking['departure'] ?? $booking['check_out'] ?? null,
                'source' => 'Beds24',
                'external_id' => (string) ($booking['bookingId'] ?? $booking['id'] ?? uniqid()),
            ];
        }

        return $reservations;
    }

    public function verifyConnection(Property $property): bool
    {
        // In production this would hit the Beds24 API with stored credentials.
        return ! empty($property->pms_integration_type);
    }
}
