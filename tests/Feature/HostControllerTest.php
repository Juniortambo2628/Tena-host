<?php

use App\Models\AccessPoint;
use App\Models\Amenity;
use App\Models\Campaign;
use App\Models\Guest;
use App\Models\Order;
use App\Models\Property;
use App\Models\Registration;
use App\Models\User;

beforeEach(function () {
    $this->host = User::factory()->host()->create();
    $this->property = Property::factory()->create(['user_id' => $this->host->id]);
});

// ─── Property CRUD ────────────────────────────────────────────────────────────

it('allows host to list their properties', function () {
    Property::factory()->count(3)->create(['user_id' => $this->host->id]);

    $response = $this->actingAs($this->host)->get(route('host.properties.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Host/Properties/Index')
        ->has('properties', 4)
    );
});

it('allows host to create a property', function () {
    $response = $this->actingAs($this->host)->post(route('host.properties.store'), [
        'name' => 'New Lodge',
        'address' => '123 Main St',
        'occupancy_threshold' => 20,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');
    $this->assertDatabaseHas('properties', [
        'user_id' => $this->host->id,
        'name' => 'New Lodge',
        'address' => '123 Main St',
    ]);
});

it('allows host to view a property', function () {
    $response = $this->actingAs($this->host)->get(route('host.properties.show', $this->property));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Host/Properties/Show')
        ->has('property')
    );
});

it('allows host to update a property', function () {
    $response = $this->actingAs($this->host)->put(route('host.properties.update', $this->property), [
        'name' => 'Updated Lodge',
        'occupancy_threshold' => 30,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');
    $this->assertDatabaseHas('properties', [
        'id' => $this->property->id,
        'name' => 'Updated Lodge',
        'occupancy_threshold' => 30,
    ]);
});

it('allows host to delete a property', function () {
    $response = $this->actingAs($this->host)->delete(route('host.properties.destroy', $this->property));

    $response->assertRedirect();
    $response->assertSessionHas('success');
    $this->assertSoftDeleted('properties', ['id' => $this->property->id]);
});

it('validates required fields when creating a property', function () {
    $response = $this->actingAs($this->host)->post(route('host.properties.store'), []);

    $response->assertSessionHasErrors('name');
});

it('prevents host from viewing another host\'s property', function () {
    $otherHost = User::factory()->host()->create();
    $otherProperty = Property::factory()->create(['user_id' => $otherHost->id]);

    $response = $this->actingAs($this->host)->get(route('host.properties.show', $otherProperty));

    $response->assertStatus(403);
});

it('prevents host from updating another host\'s property', function () {
    $otherHost = User::factory()->host()->create();
    $otherProperty = Property::factory()->create(['user_id' => $otherHost->id]);

    $response = $this->actingAs($this->host)->put(route('host.properties.update', $otherProperty), [
        'name' => 'Hacked',
    ]);

    $response->assertStatus(403);
});

// ─── Guest Management ─────────────────────────────────────────────────────────

it('allows host to list guests', function () {
    Guest::factory()->count(2)->create(['property_id' => $this->property->id]);

    $response = $this->actingAs($this->host)->get(route('host.guests.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Host/Guests/Index')
        ->has('guests.data', 2)
    );
});

it('allows host to create a guest', function () {
    $response = $this->actingAs($this->host)->post(route('host.guests.store'), [
        'property_id' => $this->property->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');
    $this->assertDatabaseHas('guests', [
        'property_id' => $this->property->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
    ]);
});

it('allows host to view a guest', function () {
    $guest = Guest::factory()->create(['property_id' => $this->property->id]);

    $response = $this->actingAs($this->host)->get(route('host.guests.show', $guest));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Host/Guests/Show')
        ->has('guest')
    );
});

it('allows host to update a guest', function () {
    $guest = Guest::factory()->create(['property_id' => $this->property->id]);

    $response = $this->actingAs($this->host)->put(route('host.guests.update', $guest), [
        'first_name' => 'Jane',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');
    $this->assertDatabaseHas('guests', [
        'id' => $guest->id,
        'first_name' => 'Jane',
    ]);
});

it('allows host to delete a guest', function () {
    $guest = Guest::factory()->create(['property_id' => $this->property->id]);

    $response = $this->actingAs($this->host)->delete(route('host.guests.destroy', $guest));

    $response->assertRedirect();
    $response->assertSessionHas('success');
    $this->assertSoftDeleted('guests', ['id' => $guest->id]);
});

it('validates required fields when creating a guest', function () {
    $response = $this->actingAs($this->host)->post(route('host.guests.store'), []);

    $response->assertSessionHasErrors(['property_id', 'first_name', 'last_name', 'email']);
});

// ─── Order Management ─────────────────────────────────────────────────────────

it('allows host to list orders', function () {
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

it('allows host to view an order', function () {
    $guest = Guest::factory()->create(['property_id' => $this->property->id]);
    $amenity = Amenity::factory()->create(['property_id' => $this->property->id]);
    $order = Order::factory()->create([
        'property_id' => $this->property->id,
        'guest_id' => $guest->id,
        'amenity_id' => $amenity->id,
    ]);

    $response = $this->actingAs($this->host)->get(route('host.orders.show', $order));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Host/Orders/Show')
        ->has('order')
    );
});

it('allows host to update order status', function () {
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
    $response->assertSessionHas('success');
    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'status' => 'fulfilled',
    ]);
});

it('allows host to delete an order', function () {
    $guest = Guest::factory()->create(['property_id' => $this->property->id]);
    $amenity = Amenity::factory()->create(['property_id' => $this->property->id]);
    $order = Order::factory()->create([
        'property_id' => $this->property->id,
        'guest_id' => $guest->id,
        'amenity_id' => $amenity->id,
    ]);

    $response = $this->actingAs($this->host)->delete(route('host.orders.destroy', $order));

    $response->assertRedirect();
    $response->assertSessionHas('success');
    $this->assertDatabaseMissing('orders', ['id' => $order->id]);
});

it('validates status values when updating an order', function () {
    $guest = Guest::factory()->create(['property_id' => $this->property->id]);
    $amenity = Amenity::factory()->create(['property_id' => $this->property->id]);
    $order = Order::factory()->create([
        'property_id' => $this->property->id,
        'guest_id' => $guest->id,
        'amenity_id' => $amenity->id,
    ]);

    $response = $this->actingAs($this->host)->put(route('host.orders.update', $order), [
        'status' => 'invalid_status',
    ]);

    $response->assertSessionHasErrors('status');
});

// ─── Amenity Management ───────────────────────────────────────────────────────

it('allows host to list amenities', function () {
    Amenity::factory()->count(3)->create(['property_id' => $this->property->id]);

    $response = $this->actingAs($this->host)->get(route('host.amenities.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Host/Amenities/Index')
        ->has('amenities.data', 3)
    );
});

it('allows host to create an amenity', function () {
    $response = $this->actingAs($this->host)->post(route('host.amenities.store'), [
        'property_id' => $this->property->id,
        'name' => 'Breakfast',
        'description' => 'Continental breakfast',
        'price' => 15.00,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');
    $this->assertDatabaseHas('amenities', [
        'property_id' => $this->property->id,
        'name' => 'Breakfast',
        'price' => 15.00,
    ]);
});

it('allows host to view an amenity', function () {
    $amenity = Amenity::factory()->create(['property_id' => $this->property->id]);

    $response = $this->actingAs($this->host)->get(route('host.amenities.show', $amenity));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Host/Amenities/Show')
        ->has('amenity')
    );
});

it('allows host to update an amenity', function () {
    $amenity = Amenity::factory()->create(['property_id' => $this->property->id]);

    $response = $this->actingAs($this->host)->put(route('host.amenities.update', $amenity), [
        'name' => 'Updated Amenity',
        'price' => 25.00,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');
    $this->assertDatabaseHas('amenities', [
        'id' => $amenity->id,
        'name' => 'Updated Amenity',
        'price' => 25.00,
    ]);
});

it('allows host to delete an amenity', function () {
    $amenity = Amenity::factory()->create(['property_id' => $this->property->id]);

    $response = $this->actingAs($this->host)->delete(route('host.amenities.destroy', $amenity));

    $response->assertRedirect();
    $response->assertSessionHas('success');
    $this->assertDatabaseMissing('amenities', ['id' => $amenity->id]);
});

// ─── Access Point Management ──────────────────────────────────────────────────

it('allows host to list access points', function () {
    AccessPoint::factory()->count(3)->create(['property_id' => $this->property->id]);

    $response = $this->actingAs($this->host)->get(route('host.access-points.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Host/AccessPoints/Index')
        ->has('accessPoints.data', 3)
    );
});

it('allows host to create an access point', function () {
    $response = $this->actingAs($this->host)->post(route('host.access-points.store'), [
        'property_id' => $this->property->id,
        'name' => 'Lobby AP',
        'mac_address' => 'AA:BB:CC:DD:EE:FF',
        'status' => 'online',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');
    $this->assertDatabaseHas('access_points', [
        'property_id' => $this->property->id,
        'name' => 'Lobby AP',
        'mac_address' => 'AA:BB:CC:DD:EE:FF',
    ]);
});

it('allows host to view an access point', function () {
    $accessPoint = AccessPoint::factory()->create(['property_id' => $this->property->id]);

    $response = $this->actingAs($this->host)->get(route('host.access-points.show', $accessPoint));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Host/AccessPoints/Show')
        ->has('accessPoint')
    );
});

it('allows host to update an access point', function () {
    $accessPoint = AccessPoint::factory()->create(['property_id' => $this->property->id]);

    $response = $this->actingAs($this->host)->put(route('host.access-points.update', $accessPoint), [
        'name' => 'Updated AP',
        'status' => 'offline',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');
    $this->assertDatabaseHas('access_points', [
        'id' => $accessPoint->id,
        'name' => 'Updated AP',
        'status' => 'offline',
    ]);
});

it('allows host to delete an access point', function () {
    $accessPoint = AccessPoint::factory()->create(['property_id' => $this->property->id]);

    $response = $this->actingAs($this->host)->delete(route('host.access-points.destroy', $accessPoint));

    $response->assertRedirect();
    $response->assertSessionHas('success');
    $this->assertDatabaseMissing('access_points', ['id' => $accessPoint->id]);
});

// ─── Marketing Campaigns ──────────────────────────────────────────────────────

it('allows host to list campaigns', function () {
    Campaign::factory()->count(3)->create(['user_id' => $this->host->id]);

    $response = $this->actingAs($this->host)->get(route('host.marketing.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Host/Marketing/Index')
        ->has('campaigns', 3)
        ->has('stats')
    );
});

it('allows host to create a campaign', function () {
    $response = $this->actingAs($this->host)->post(route('host.marketing.store'), [
        'name' => 'Welcome Campaign',
        'type' => 'email',
        'subject' => 'Welcome!',
        'content' => 'Thanks for booking.',
        'target_audience' => 'all_guests',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');
    $this->assertDatabaseHas('campaigns', [
        'user_id' => $this->host->id,
        'name' => 'Welcome Campaign',
        'type' => 'email',
        'status' => 'draft',
    ]);
});

it('allows host to view campaign analytics', function () {
    $campaign = Campaign::factory()->create(['user_id' => $this->host->id]);

    $response = $this->actingAs($this->host)->get(route('host.marketing.analytics', $campaign));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Host/Marketing/Analytics')
        ->has('campaign')
        ->has('performance')
        ->has('summary')
    );
});

it('allows host to activate a campaign', function () {
    $campaign = Campaign::factory()->create([
        'user_id' => $this->host->id,
        'status' => 'draft',
        'target_audience' => 'all_guests',
    ]);

    $response = $this->actingAs($this->host)->post(route('host.marketing.activate', $campaign));

    $response->assertRedirect();
    $response->assertSessionHas('success');
    expect($campaign->fresh()->status)->toBe('active');
});

it('allows host to pause a campaign', function () {
    $campaign = Campaign::factory()->create([
        'user_id' => $this->host->id,
        'status' => 'active',
    ]);

    $response = $this->actingAs($this->host)->post(route('host.marketing.pause', $campaign));

    $response->assertRedirect();
    $response->assertSessionHas('success');
    expect($campaign->fresh()->status)->toBe('paused');
});

it('allows host to delete a campaign', function () {
    $campaign = Campaign::factory()->create(['user_id' => $this->host->id]);

    $response = $this->actingAs($this->host)->delete(route('host.marketing.destroy', $campaign));

    $response->assertRedirect();
    $response->assertSessionHas('success');
    $this->assertDatabaseMissing('campaigns', ['id' => $campaign->id]);
});

// ─── Waitlist ─────────────────────────────────────────────────────────────────

it('allows public user to submit waitlist form', function () {
    $response = $this->postJson(route('waitlist.store'), [
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'email' => 'jane@example.com',
        'property_type' => 'Entire Place',
        'units' => '1-5',
        'primary_platform' => 'Airbnb',
        'biggest_challenge' => 'Getting more direct bookings',
    ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('registrations', [
        'email' => 'jane@example.com',
        'first_name' => 'Jane',
        'status' => 'active',
    ]);
});

it('validates required fields on waitlist', function () {
    $response = $this->postJson(route('waitlist.store'), []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'first_name',
        'last_name',
        'email',
        'property_type',
        'units',
        'primary_platform',
        'biggest_challenge',
    ]);
});

it('validates email format on waitlist', function () {
    $response = $this->postJson(route('waitlist.store'), [
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'email' => 'not-an-email',
        'property_type' => 'Entire Place',
        'units' => '1-5',
        'primary_platform' => 'Airbnb',
        'biggest_challenge' => 'Getting more direct bookings',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('email');
});

// ─── Host Dashboard ───────────────────────────────────────────────────────────

it('allows host to access dashboard', function () {
    $response = $this->actingAs($this->host)->get(route('host.dashboard'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Host/Dashboard')
        ->has('properties')
        ->has('stats')
        ->has('guestChartData')
    );
});

it('shows correct stats on dashboard', function () {
    Guest::factory()->count(3)->create(['property_id' => $this->property->id]);
    Amenity::factory()->create(['property_id' => $this->property->id]);

    $response = $this->actingAs($this->host)->get(route('host.dashboard'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Host/Dashboard')
        ->where('stats.totalGuests', 3)
        ->where('stats.totalProperties', 1)
    );
});
