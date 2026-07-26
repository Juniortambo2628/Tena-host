<?php

use App\Models\Guest;
use App\Models\Property;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
});

it('allows a guest to request an OTP via email', function () {
    $property = Property::factory()->create();
    $guest = Guest::factory()->create([
        'property_id' => $property->id,
    ]);

    $response = $this->post(route('guest.otp.send'), [
        'email' => $guest->email,
        'property_id' => $property->id,
    ]);

    $response->assertRedirect(route('guest.otp.verify.form', ['email' => $guest->email]));
    $response->assertSessionHas('success');

    expect(\App\Models\Otp::where('identifier', $guest->email)->exists())->toBeTrue();
});

it('verifies a guest OTP and logs them in', function () {
    $property = Property::factory()->create();
    $guest = Guest::factory()->create([
        'property_id' => $property->id,
    ]);

    $otp = app(OtpService::class)->send($guest->email);

    $response = $this->post(route('guest.otp.verify'), [
        'email' => $guest->email,
        'code' => $otp->code,
    ]);

    $response->assertRedirect(route('guest.portal'));
    $response->assertSessionHas('success');
    expect(auth()->user())->not->toBeNull();
    expect(auth()->user()->role)->toBe('guest');
});

it('allows a logged-in guest to view their assigned property portal', function () {
    $property = Property::factory()->create();
    $guest = Guest::factory()->create([
        'property_id' => $property->id,
    ]);

    $user = User::factory()->guest()->create([
        'email' => $guest->email,
    ]);
    $guest->update(['user_id' => $user->id]);

    $response = $this->actingAs($user)->get(route('guest.portal'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Guest/Portal')
        ->has('property')
        ->where('property.id', $property->id)
    );
});

it('prevents a guest from viewing an unassigned property portal', function () {
    $property = Property::factory()->create();
    $otherProperty = Property::factory()->create();
    $guest = Guest::factory()->create([
        'property_id' => $property->id,
    ]);

    $user = User::factory()->guest()->create([
        'email' => $guest->email,
    ]);
    $guest->update(['user_id' => $user->id]);

    $response = $this->actingAs($user)->get(route('guest.portal', ['p' => $otherProperty->id]));

    $response->assertForbidden();
});

it('allows a guest to place an order for an amenity', function () {
    $property = Property::factory()->create();
    $amenity = \App\Models\Amenity::factory()->create([
        'property_id' => $property->id,
        'price' => 500,
    ]);
    $guest = Guest::factory()->create([
        'property_id' => $property->id,
    ]);

    $user = User::factory()->guest()->create([
        'email' => $guest->email,
    ]);
    $guest->update(['user_id' => $user->id]);

    $response = $this->actingAs($user)->post(route('guest.orders.store'), [
        'amenity_id' => $amenity->id,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    expect(\App\Models\Order::where('guest_id', $guest->id)->where('amenity_id', $amenity->id)->exists())->toBeTrue();
});
