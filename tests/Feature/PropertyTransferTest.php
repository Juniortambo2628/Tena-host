<?php

use App\Models\AccessPoint;
use App\Models\Campaign;
use App\Models\DeviceAgreement;
use App\Models\Event;
use App\Models\Guest;
use App\Models\Notification;
use App\Models\Property;
use App\Models\PropertyTransfer;
use App\Models\Setting;
use App\Models\User;

beforeEach(function () {
    Setting::setValue('property_transfers_enabled', '1');
    $this->owner = User::factory()->host()->create();
    $this->newOwner = User::factory()->host()->create(['email' => 'new.owner@example.com']);
    $this->property = Property::factory()->create(['user_id' => $this->owner->id, 'pms_integration_type' => 'hostaway', 'pms_connection_status' => 'connected']);
});

function offerTransfer(array $overrides = []): PropertyTransfer
{
    test()->actingAs(test()->owner)->post(route('host.transfers.store'), $overrides + [
        'property_id' => test()->property->id,
        'email' => 'New.Owner@example.com ',
        'password' => 'password',
    ])->assertSessionHasNoErrors();

    return PropertyTransfer::latest('id')->firstOrFail();
}

it('stays hidden until Admin -> Settings switches it on', function () {
    Setting::setValue('property_transfers_enabled', '0');

    $this->actingAs($this->owner)->get(route('host.transfers.index'))->assertNotFound();
    $this->actingAs($this->owner)->post(route('host.transfers.store'), ['property_id' => $this->property->id, 'email' => 'x@example.com', 'password' => 'password'])->assertNotFound();
});

it('lets only the owner offer a property, after re-entering their password', function () {
    // A team member can't.
    $member = User::factory()->host()->create(['team_owner_id' => $this->owner->id, 'team_role' => 'manager']);
    $this->actingAs($member)->post(route('host.transfers.store'), ['property_id' => $this->property->id, 'email' => 'x@example.com', 'password' => 'password'])->assertForbidden();

    // Another host can't offer someone else's property.
    $this->actingAs($this->newOwner)->post(route('host.transfers.store'), ['property_id' => $this->property->id, 'email' => 'x@example.com', 'password' => 'password'])
        ->assertSessionHasErrors('property_id');

    // The wrong password is refused.
    $this->actingAs($this->owner)->post(route('host.transfers.store'), ['property_id' => $this->property->id, 'email' => 'new.owner@example.com', 'password' => 'wrong'])
        ->assertSessionHasErrors('password');

    expect(PropertyTransfer::count())->toBe(0);
});

it('offers the property, tells both sides, and keeps it with the owner until accepted', function () {
    $transfer = offerTransfer();

    expect($transfer->status)->toBe('pending')
        ->and($transfer->to_email)->toBe('new.owner@example.com')
        ->and($transfer->expires_at->isFuture())->toBeTrue()
        ->and($this->property->fresh()->user_id)->toBe($this->owner->id)
        ->and(Notification::where('user_id', $this->newOwner->id)->exists())->toBeTrue()
        ->and(Notification::where('user_id', $this->owner->id)->exists())->toBeTrue();

    // The recipient sees it waiting, with what would move.
    $this->actingAs($this->newOwner)->get(route('host.transfers.index'))->assertOk()
        ->assertInertia(fn ($page) => $page->component('Host/Transfers/Index')
            ->where('incoming.0.property', $this->property->name)
            ->where('incoming.0.status', 'pending')
            ->where('auth.propertyTransfers.incoming', 1));

    // A second offer for the same property waits for the first to be cancelled.
    $this->actingAs($this->owner)->post(route('host.transfers.store'), ['property_id' => $this->property->id, 'email' => 'other@example.com', 'password' => 'password'])
        ->assertSessionHasErrors('property_id');
});

it('moves the property and everything linked to it once the accepted transfer has cooled off', function () {
    $guest = Guest::factory()->create(['property_id' => $this->property->id]);
    $ap = AccessPoint::factory()->create(['property_id' => $this->property->id]);
    $campaign = Campaign::factory()->create(['property_id' => $this->property->id, 'user_id' => $this->owner->id]);
    $event = Event::create(['user_id' => $this->owner->id, 'property_id' => $this->property->id, 'name' => 'Launch', 'starts_on' => '2026-12-01', 'ends_on' => '2026-12-01']);
    $member = User::factory()->host()->create(['team_owner_id' => $this->owner->id, 'team_role' => 'manager']);
    $member->staffProperties()->attach($this->property->id);
    // Their access is cached; it must still go straight away.
    expect($member->can('update', $this->property))->toBeTrue()
        ->and($this->owner->can('update', $this->property))->toBeTrue();
    $transfer = offerTransfer();

    // Someone else signed in can't accept it.
    $this->actingAs(User::factory()->host()->create())->post(route('host.transfers.accept', $transfer))->assertForbidden();

    $this->actingAs($this->newOwner)->post(route('host.transfers.accept', $transfer))->assertSessionHasNoErrors();

    // Accepting starts the cooling-off: nothing moves yet.
    expect($transfer->fresh()->status)->toBe('accepted')
        ->and($this->property->fresh()->user_id)->toBe($this->owner->id);
    $this->artisan('transfers:complete')->assertSuccessful();
    expect($this->property->fresh()->user_id)->toBe($this->owner->id);

    $this->travel(PropertyTransfer::COOLING_OFF_HOURS)->hours();
    $this->travel(1)->minutes();
    $this->artisan('transfers:complete')->assertSuccessful();

    $property = $this->property->fresh();
    expect($property->user_id)->toBe($this->newOwner->id)
        ->and($property->pms_connection_status)->toBe('disconnected')
        ->and($guest->fresh()->property_id)->toBe($property->id)
        ->and($ap->fresh()->property_id)->toBe($property->id)
        ->and($campaign->fresh()->user_id)->toBe($this->newOwner->id)
        ->and($event->fresh()->user_id)->toBe($this->newOwner->id)
        ->and($member->staffProperties()->count())->toBe(0)
        ->and($member->fresh()->can('update', $property))->toBeFalse()
        ->and($this->owner->can('update', $property))->toBeFalse()
        ->and($this->newOwner->can('update', $property))->toBeTrue();

    $transfer->refresh();
    expect($transfer->status)->toBe('completed')
        ->and($transfer->to_user_id)->toBe($this->newOwner->id)
        ->and($transfer->moved)->toEqual(['guests' => 1, 'access_points' => 1, 'campaigns' => 1, 'events' => 1]);

    // It can't be accepted twice.
    $this->actingAs($this->newOwner)->post(route('host.transfers.accept', $transfer))->assertSessionHasErrors('transfer');
});

it('lets the recipient decline and the owner cancel, and nothing moves', function () {
    $declined = offerTransfer();
    $this->actingAs($this->newOwner)->post(route('host.transfers.decline', $declined))->assertSessionHasNoErrors();
    expect($declined->fresh()->status)->toBe('declined');

    $cancelled = offerTransfer();
    // Only the owner who offered it can cancel.
    $this->actingAs($this->newOwner)->post(route('host.transfers.cancel', $cancelled))->assertForbidden();
    $this->actingAs($this->owner)->post(route('host.transfers.cancel', $cancelled))->assertSessionHasNoErrors();
    expect($cancelled->fresh()->status)->toBe('cancelled');

    $this->actingAs($this->newOwner)->post(route('host.transfers.accept', $cancelled))->assertSessionHasErrors('transfer');
    expect($this->property->fresh()->user_id)->toBe($this->owner->id);
});

it('lets an offer expire after a week', function () {
    $transfer = offerTransfer();
    $this->travel(PropertyTransfer::VALID_DAYS + 1)->days();

    $this->actingAs($this->newOwner)->post(route('host.transfers.accept', $transfer))->assertSessionHasErrors('transfer');
    $this->actingAs($this->newOwner)->get(route('host.transfers.index'));

    expect($transfer->fresh()->status)->toBe('expired')
        ->and($this->property->fresh()->user_id)->toBe($this->owner->id);
});

it('blocks a property whose TenaFi device is still being paid off monthly', function () {
    DeviceAgreement::record([
        'user_id' => $this->owner->id, 'property_id' => $this->property->id, 'device_type' => 'pro',
        'price_kes' => 42000, 'monthly_kes' => 5000, 'months' => 12, 'starts_on' => now()->toDateString(),
    ]);

    $this->actingAs($this->owner)->post(route('host.transfers.store'), ['property_id' => $this->property->id, 'email' => 'new.owner@example.com', 'password' => 'password'])
        ->assertSessionHasErrors('property_id');
    expect(PropertyTransfer::count())->toBe(0);
});
