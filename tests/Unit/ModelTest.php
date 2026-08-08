<?php

use App\Models\AccessPoint;
use App\Models\Amenity;
use App\Models\Campaign;
use App\Models\Guest;
use App\Models\Order;
use App\Models\PolicyDocument;
use App\Models\Property;
use App\Models\Registration;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| User Model
|--------------------------------------------------------------------------
*/

test('user has many properties', function () {
    $user = User::factory()->host()->create();

    Property::factory()->count(3)->create(['user_id' => $user->id]);

    expect($user->properties)->toHaveCount(3);
});

test('user has many guest records', function () {
    $user = User::factory()->guest()->create();

    $property = Property::factory()->create();
    Guest::factory()->count(2)->create(['property_id' => $property->id, 'user_id' => $user->id]);

    expect($user->guestRecords)->toHaveCount(2);
});

test('user belongs to many properties via staff_property', function () {
    $user = User::factory()->staff()->create();
    $properties = Property::factory()->count(3)->create();

    $user->staffProperties()->attach($properties->pluck('id'));

    expect($user->staffProperties)->toHaveCount(3);
});

test('user name accessor returns full name', function () {
    $user = User::factory()->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);

    expect($user->name)->toBe('John Doe');
});

test('user isAdmin returns true for admin role', function () {
    $admin = User::factory()->admin()->create();
    $host = User::factory()->host()->create();

    expect($admin->isAdmin())->toBeTrue()
        ->and($host->isAdmin())->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Property Model
|--------------------------------------------------------------------------
*/

test('property belongs to user as host', function () {
    $user = User::factory()->host()->create();
    $property = Property::factory()->create(['user_id' => $user->id]);

    expect($property->host->id)->toBe($user->id);
});

test('property has many access points', function () {
    $property = Property::factory()->create();
    AccessPoint::factory()->count(3)->create(['property_id' => $property->id]);

    expect($property->accessPoints)->toHaveCount(3);
});

test('property has many guests', function () {
    $property = Property::factory()->create();
    Guest::factory()->count(4)->create(['property_id' => $property->id]);

    expect($property->guests)->toHaveCount(4);
});

test('property has many amenities', function () {
    $property = Property::factory()->create();
    Amenity::factory()->count(2)->create(['property_id' => $property->id]);

    expect($property->amenities)->toHaveCount(2);
});

/*
|--------------------------------------------------------------------------
| Guest Model
|--------------------------------------------------------------------------
*/

test('guest belongs to property', function () {
    $property = Property::factory()->create();
    $guest = Guest::factory()->create(['property_id' => $property->id]);

    expect($guest->property->id)->toBe($property->id);
});

test('guest belongs to user optionally', function () {
    $user = User::factory()->create();
    $guest = Guest::factory()->create(['user_id' => $user->id]);

    expect($guest->user->id)->toBe($user->id);

    $orphan = Guest::factory()->create(['user_id' => null]);

    expect($orphan->user)->toBeNull();
});

test('guest has many orders', function () {
    $guest = Guest::factory()->create();
    $property = $guest->property;
    $amenity = Amenity::factory()->create(['property_id' => $property->id]);

    Order::factory()->count(2)->create([
        'guest_id' => $guest->id,
        'property_id' => $property->id,
        'amenity_id' => $amenity->id,
    ]);

    expect($guest->orders)->toHaveCount(2);
});

/*
|--------------------------------------------------------------------------
| Order Model
|--------------------------------------------------------------------------
*/

test('order belongs to guest', function () {
    $order = Order::factory()->create();

    expect($order->guest)->toBeInstanceOf(Guest::class);
});

test('order belongs to property', function () {
    $order = Order::factory()->create();

    expect($order->property)->toBeInstanceOf(Property::class);
});

test('order belongs to amenity', function () {
    $order = Order::factory()->create();

    expect($order->amenity)->toBeInstanceOf(Amenity::class);
});

/*
|--------------------------------------------------------------------------
| Campaign Model
|--------------------------------------------------------------------------
*/

test('campaign belongs to user', function () {
    $campaign = Campaign::factory()->create();

    expect($campaign->user)->toBeInstanceOf(User::class);
});

test('campaign belongs to property', function () {
    $property = Property::factory()->create();
    $campaign = Campaign::factory()->create(['property_id' => $property->id]);

    expect($campaign->property->id)->toBe($property->id);
});

test('campaign has many marketing events', function () {
    $property = Property::factory()->create();
    $campaign = Campaign::factory()->create(['property_id' => $property->id]);
    $guest = Guest::factory()->create(['property_id' => $property->id]);

    $campaign->events()->create([
        'guest_id' => $guest->id,
        'event_type' => 'sent',
    ]);

    $campaign->events()->create([
        'guest_id' => $guest->id,
        'event_type' => 'opened',
    ]);

    expect($campaign->events)->toHaveCount(2);
});

/*
|--------------------------------------------------------------------------
| Setting Model
|--------------------------------------------------------------------------
*/

test('setting getValue returns value for existing key', function () {
    Setting::setValue('site_name', 'Tena', 'general', 'string');

    expect(Setting::getValue('site_name'))->toBe('Tena');
});

test('setting getValue returns default for missing key', function () {
    expect(Setting::getValue('nonexistent', 'fallback'))->toBe('fallback');
});

test('setting getValue returns null for missing key without default', function () {
    expect(Setting::getValue('nonexistent'))->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Registration Model
|--------------------------------------------------------------------------
*/

test('registration can be created with all fields', function () {
    $registration = Registration::factory()->create([
        'email' => 'test@example.com',
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'property_type' => 'hotel',
        'property_count' => 5,
        'location' => 'Nairobi',
        'phone' => '+254712345678',
        'message' => 'Interested in your platform',
        'referral_source' => 'Google',
        'status' => 'active',
        'agree_updates' => true,
    ]);

    expect($registration->email)->toBe('test@example.com')
        ->and($registration->first_name)->toBe('Jane')
        ->and($registration->last_name)->toBe('Smith')
        ->and($registration->property_type)->toBe('hotel')
        ->and($registration->property_count)->toBe(5)
        ->and($registration->location)->toBe('Nairobi')
        ->and($registration->status)->toBe('active')
        ->and($registration->agree_updates)->toBeTrue();
});

test('registration status defaults to active', function () {
    $id = \Illuminate\Support\Facades\DB::table('registrations')->insertGetId([
        'email' => 'default@example.com',
        'first_name' => 'Test',
        'last_name' => 'User',
        'property_type' => 'hotel',
        'property_count' => 1,
        'location' => 'Mombasa',
    ]);

    $registration = Registration::find($id);

    expect($registration->status)->toBe('active');
});

/*
|--------------------------------------------------------------------------
| PolicyDocument Model
|--------------------------------------------------------------------------
*/

test('policy document can be created with all fields', function () {
    $policy = PolicyDocument::create([
        'slug' => 'test-privacy-policy',
        'title' => 'Privacy Policy',
        'description' => 'How we handle your data',
        'content' => '<p>Privacy policy content here</p>',
        'type' => 'privacy_policy',
        'is_published' => true,
        'version' => '1.0',
        'effective_date' => now(),
        'last_reviewed_at' => now(),
        'last_reviewed_by' => 'admin@example.com',
    ]);

    expect($policy->slug)->toBe('test-privacy-policy')
        ->and($policy->title)->toBe('Privacy Policy')
        ->and($policy->type)->toBe('privacy_policy')
        ->and($policy->is_published)->toBeTrue()
        ->and($policy->version)->toBe('1.0');
});

test('policy document is unpublished by default', function () {
    $id = \Illuminate\Support\Facades\DB::table('policy_documents')->insertGetId([
        'slug' => 'test-terms-of-service',
        'title' => 'Terms of Service',
        'content' => '<p>Terms content</p>',
        'type' => 'terms_of_service',
    ]);

    $policy = PolicyDocument::find($id);

    expect($policy->is_published)->toBeFalse();
});
