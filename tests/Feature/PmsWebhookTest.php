<?php

use App\Models\Property;

it('syncs beds24 webhook reservations into guests', function () {
    $property = Property::factory()->create([
        'pms_integration_type' => 'Beds24',
        'pms_connection_status' => 'connected',
    ]);

    $response = $this->postJson(route('api.pms.webhook', ['provider' => 'beds24']), [
        'property_id' => $property->id,
        'bookings' => [
            [
                'bookingId' => 'BEDS-001',
                'guest' => [
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                    'email' => 'john@example.com',
                    'phone' => '+254712345678',
                ],
                'arrival' => '2026-08-01',
                'departure' => '2026-08-05',
            ],
        ],
    ]);

    $response->assertOk()
        ->assertJsonPath('result.created', 1);

    $this->assertDatabaseHas('guests', [
        'property_id' => $property->id,
        'external_id' => 'BEDS-001',
        'email' => 'john@example.com',
    ]);

    expect($property->fresh()->pms_connection_status)->toBe('connected');
});

it('syncs cloudbeds webhook reservations into guests', function () {
    $property = Property::factory()->create([
        'pms_integration_type' => 'Cloudbeds',
        'pms_connection_status' => 'connected',
    ]);

    $response = $this->postJson(route('api.pms.webhook', ['provider' => 'cloudbeds']), [
        'property_id' => $property->id,
        'reservations' => [
            [
                'reservation_id' => 'CB-001',
                'guest' => [
                    'first_name' => 'Jane',
                    'last_name' => 'Smith',
                    'email' => 'jane@example.com',
                ],
                'checkin' => '2026-09-01',
                'checkout' => '2026-09-03',
            ],
        ],
    ]);

    $response->assertOk()
        ->assertJsonPath('result.created', 1);

    $this->assertDatabaseHas('guests', [
        'property_id' => $property->id,
        'external_id' => 'CB-001',
        'email' => 'jane@example.com',
    ]);
});

it('returns 404 when property is not found', function () {
    $response = $this->postJson(route('api.pms.webhook', ['provider' => 'beds24']), [
        'property_id' => 999999,
    ]);

    $response->assertNotFound();
});

it('rejects webhooks with mismatched provider', function () {
    $property = Property::factory()->create([
        'pms_integration_type' => 'Hostaway',
        'pms_connection_status' => 'connected',
    ]);

    $response = $this->postJson(route('api.pms.webhook', ['provider' => 'beds24']), [
        'property_id' => $property->id,
    ]);

    $response->assertStatus(400)
        ->assertJsonPath('message', 'Provider mismatch.');
});

it('rejects webhooks with invalid signature when secret is configured', function () {
    config(['services.pms.webhook_secret' => 'secret-key']);

    $property = Property::factory()->create([
        'pms_integration_type' => 'Beds24',
        'pms_connection_status' => 'connected',
    ]);

    $response = $this->postJson(route('api.pms.webhook', ['provider' => 'beds24']), [
        'property_id' => $property->id,
    ], ['X-PMS-Signature' => 'wrong-key']);

    $response->assertUnauthorized();
});

it('accepts webhooks with valid signature when secret is configured', function () {
    config(['services.pms.webhook_secret' => 'secret-key']);

    $property = Property::factory()->create([
        'pms_integration_type' => 'Beds24',
        'pms_connection_status' => 'connected',
    ]);

    $response = $this->postJson(route('api.pms.webhook', ['provider' => 'beds24']), [
        'property_id' => $property->id,
        'bookings' => [
            [
                'bookingId' => 'BEDS-002',
                'guest' => [
                    'firstName' => 'Signed',
                    'lastName' => 'Webhook',
                    'email' => 'signed@example.com',
                ],
            ],
        ],
    ], ['X-PMS-Signature' => 'secret-key']);

    $response->assertOk();

    $this->assertDatabaseHas('guests', [
        'property_id' => $property->id,
        'external_id' => 'BEDS-002',
    ]);
});
