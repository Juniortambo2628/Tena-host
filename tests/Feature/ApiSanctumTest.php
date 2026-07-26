<?php

use App\Models\Guest;
use App\Models\Order;
use App\Models\Property;
use App\Models\User;

beforeEach(function () {
    $this->host = User::factory()->host()->create();
    $this->token = $this->host->createToken('api-test')->plainTextToken;
    $this->property = Property::factory()->create(['user_id' => $this->host->id]);
});

it('returns authenticated user', function () {
    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson('/api/user');

    $response->assertOk()
        ->assertJsonPath('email', $this->host->email);
});

it('lists host properties via api', function () {
    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson('/api/host/properties');

    $response->assertOk()
        ->assertJsonPath('data.0.id', $this->property->id);
});

it('stores a new property via api', function () {
    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson('/api/host/properties', [
            'name' => 'API Property',
            'address' => '123 API Lane',
            'wifi_ssid' => 'API_WiFi',
        ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'API Property');

    $this->assertDatabaseHas('properties', [
        'name' => 'API Property',
        'user_id' => $this->host->id,
    ]);
});

it('prevents hosts from viewing other hosts properties via api', function () {
    $otherHost = User::factory()->host()->create();
    $otherProperty = Property::factory()->create(['user_id' => $otherHost->id]);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson("/api/host/properties/{$otherProperty->id}");

    $response->assertForbidden();
});

it('lists guests scoped to host properties via api', function () {
    Guest::factory()->count(3)->create(['property_id' => $this->property->id]);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson('/api/host/guests');

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

it('lists orders scoped to host properties via api', function () {
    $guest = Guest::factory()->create(['property_id' => $this->property->id]);
    $amenity = \App\Models\Amenity::factory()->create(['property_id' => $this->property->id]);
    Order::factory()->count(2)->create([
        'property_id' => $this->property->id,
        'guest_id' => $guest->id,
        'amenity_id' => $amenity->id,
    ]);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson('/api/host/orders');

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

it('allows guests to view portal and place orders via api', function () {
    $property = Property::factory()->create();
    $amenity = \App\Models\Amenity::factory()->create([
        'property_id' => $property->id,
        'price' => 250,
    ]);
    $guest = Guest::factory()->create([
        'property_id' => $property->id,
        'email' => 'api-guest@example.com',
    ]);
    $user = User::factory()->create(['role' => 'guest', 'email' => $guest->email]);
    $guest->update(['user_id' => $user->id]);
    $token = $user->createToken('guest-api')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/guest/portal');

    $response->assertOk()
        ->assertJsonPath('property.id', $property->id);

    $orderResponse = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/guest/orders', ['amenity_id' => $amenity->id]);

    $orderResponse->assertCreated()
        ->assertJsonPath('order.amenity_id', $amenity->id);
});

it('rejects unauthenticated api requests', function () {
    $response = $this->getJson('/api/host/properties');

    $response->assertUnauthorized();
});
