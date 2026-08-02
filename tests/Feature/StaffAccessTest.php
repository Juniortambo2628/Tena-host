<?php

use App\Models\Guest;
use App\Models\Property;
use App\Models\User;

it('allows staff to view only assigned properties', function () {
    $host = User::factory()->host()->create();
    $assignedProperty = Property::factory()->create(['user_id' => $host->id]);
    $otherProperty = Property::factory()->create(['user_id' => $host->id]);

    $staff = User::factory()->staff()->create();
    $staff->staffProperties()->attach($assignedProperty);

    $response = $this->actingAs($staff)->get(route('staff.dashboard'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Staff/Dashboard')
        ->has('properties', 1)
        ->where('properties.0.id', $assignedProperty->id)
    );
});

it('prevents staff from accessing host-only routes', function () {
    $staff = User::factory()->staff()->create();

    $response = $this->actingAs($staff)->get(route('host.guests.index'));

    $response->assertForbidden();
});

it('prevents hosts from accessing staff dashboard', function () {
    $host = User::factory()->host()->create();

    $response = $this->actingAs($host)->get(route('staff.dashboard'));

    $response->assertForbidden();
});

it('shows recent guests and pending orders scoped to assigned properties', function () {
    $host = User::factory()->host()->create();
    $assignedProperty = Property::factory()->create(['user_id' => $host->id]);
    $otherProperty = Property::factory()->create(['user_id' => $host->id]);

    $staff = User::factory()->staff()->create();
    $staff->staffProperties()->attach($assignedProperty);

    $assignedGuest = Guest::factory()->create(['property_id' => $assignedProperty->id]);
    $otherGuest = Guest::factory()->create(['property_id' => $otherProperty->id]);

    $response = $this->actingAs($staff)->get(route('staff.dashboard'));

    $response->assertInertia(fn ($page) => $page
        ->component('Staff/Dashboard')
        ->has('recentGuests', 1)
        ->where('recentGuests.0.id', $assignedGuest->id)
        ->has('pendingOrders', 0)
    );
});
