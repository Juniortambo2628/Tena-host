<?php

use App\Models\AccessPoint;
use App\Models\Amenity;
use App\Models\Guest;
use App\Models\Order;
use App\Models\Property;
use App\Models\User;

beforeEach(function () {
    $this->host = User::factory()->host()->create();
    $this->property = Property::factory()->create(['user_id' => $this->host->id]);
});

it('allows hosts to list their access points', function () {
    AccessPoint::factory()->count(3)->create(['property_id' => $this->property->id]);

    $response = $this->actingAs($this->host)->get(route('host.access-points.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Host/AccessPoints/Index')
        ->has('accessPoints.data', 3)
    );
});

it('allows hosts to create an access point', function () {
    $response = $this->actingAs($this->host)->post(route('host.access-points.store'), [
        'property_id' => $this->property->id,
        'name' => 'Lobby AP',
        'mac_address' => 'AA:BB:CC:DD:EE:FF',
        'status' => 'online',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('access_points', [
        'property_id' => $this->property->id,
        'name' => 'Lobby AP',
        'mac_address' => 'AA:BB:CC:DD:EE:FF',
    ]);
});

it('allows hosts to list their amenities', function () {
    Amenity::factory()->count(2)->create(['property_id' => $this->property->id]);

    $response = $this->actingAs($this->host)->get(route('host.amenities.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Host/Amenities/Index')
        ->has('amenities.data', 2)
    );
});

it('allows hosts to create an amenity', function () {
    $response = $this->actingAs($this->host)->post(route('host.amenities.store'), [
        'property_id' => $this->property->id,
        'name' => 'Breakfast',
        'description' => 'Continental breakfast',
        'price' => 15.00,
        'is_active' => true,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('amenities', [
        'property_id' => $this->property->id,
        'name' => 'Breakfast',
        'price' => 15.00,
    ]);
});

it('allows hosts to list their orders', function () {
    $guest = Guest::factory()->create(['property_id' => $this->property->id]);
    $amenity = Amenity::factory()->create(['property_id' => $this->property->id]);
    Order::factory()->count(2)->create([
        'property_id' => $this->property->id,
        'guest_id' => $guest->id,
        'amenity_id' => $amenity->id,
    ]);

    $response = $this->actingAs($this->host)->get(route('host.orders.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Host/Orders/Index')
        ->has('orders.data', 2)
    );
});

it('allows hosts to update order status', function () {
    $guest = Guest::factory()->create(['property_id' => $this->property->id]);
    $amenity = Amenity::factory()->create(['property_id' => $this->property->id]);
    $order = Order::factory()->create([
        'property_id' => $this->property->id,
        'guest_id' => $guest->id,
        'amenity_id' => $amenity->id,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($this->host)->put(route('host.orders.update', $order), [
        'status' => 'fulfilled',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'status' => 'fulfilled',
    ]);
});

it('prevents hosts from viewing orders of other hosts', function () {
    $otherHost = User::factory()->host()->create();
    $otherProperty = Property::factory()->create(['user_id' => $otherHost->id]);
    $guest = Guest::factory()->create(['property_id' => $otherProperty->id]);
    $amenity = Amenity::factory()->create(['property_id' => $otherProperty->id]);
    $order = Order::factory()->create([
        'property_id' => $otherProperty->id,
        'guest_id' => $guest->id,
        'amenity_id' => $amenity->id,
    ]);

    $response = $this->actingAs($this->host)->get(route('host.orders.show', $order));

    $response->assertStatus(403);
});
