<?php

namespace App\Services\Pms;

use App\Models\Property;

interface PmsDriver
{
    /**
     * Validate incoming webhook payload and return reservation records.
     *
     * @return array<int, array{
     *     first_name: string,
     *     last_name: string,
     *     email: string,
     *     phone?: string,
     *     check_in?: string,
     *     check_out?: string,
     *     source?: string,
     *     external_id: string
     * }>
     */
    public function parseReservations(Property $property, array $payload): array;

    /**
     * Test connectivity with the provider using stored credentials.
     */
    public function verifyConnection(Property $property): bool;
}
