<?php

use App\Models\DeviceAgreement;
use App\Models\Notification;
use App\Models\Property;
use App\Models\PropertyTransfer;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Setting::setValue('property_transfers_enabled', '1');
    Mail::fake();
    $this->owner = User::factory()->host()->create();
    $this->newOwner = User::factory()->host()->create(['email' => 'new.owner@example.com']);
    $this->admin = User::factory()->admin()->create();
    $this->property = Property::factory()->create(['user_id' => $this->owner->id]);
    $this->agreement = DeviceAgreement::record([
        'user_id' => $this->owner->id, 'property_id' => $this->property->id, 'device_type' => 'pro',
        'price_kes' => 42000, 'monthly_kes' => 5000, 'months' => 12, 'starts_on' => now()->toDateString(),
    ]);
});

function offerProperty(array $overrides = [])
{
    return test()->actingAs(test()->owner)->post(route('host.transfers.store'), $overrides + [
        'property_id' => test()->property->id, 'email' => 'new.owner@example.com', 'password' => 'password',
    ]);
}

it('asks the owner to pay off the device first, or to hand over the payments', function () {
    offerProperty()->assertSessionHasErrors(['property_id' => 'A TenaFi device here is still being paid off monthly: KES 60,000 left. Pay it off first (TenaFi records the payment), or choose that the new owner takes over the payments.']);
    expect(PropertyTransfer::count())->toBe(0);

    // The page shows what's owed on each property.
    $this->actingAs($this->owner)->get(route('host.transfers.index'))
        ->assertInertia(fn ($page) => $page->where('properties.0.devices.balance', 60000)->where('properties.0.devices.count', 1));

    offerProperty(['device_takeover' => true])->assertSessionHasNoErrors();
    $transfer = PropertyTransfer::firstOrFail();
    expect($transfer->device_takeover)->toBeTrue()
        ->and($transfer->device_count)->toBe(1)
        ->and($transfer->device_balance_kes)->toBe(60000)
        ->and($transfer->device_monthly_kes)->toBe(5000);

    $this->actingAs($this->newOwner)->get(route('host.transfers.index'))
        ->assertInertia(fn ($page) => $page->where('incoming.0.devices.balance', 60000));
});

it('needs the new owner to agree, holds it for TenaFi to check, then moves the payments with the property', function () {
    offerProperty(['device_takeover' => true]);
    $transfer = PropertyTransfer::firstOrFail();

    $this->actingAs($this->newOwner)->post(route('host.transfers.accept', $transfer))->assertSessionHasErrors('takeover');
    $this->actingAs($this->newOwner)->post(route('host.transfers.accept', $transfer), ['takeover' => true])->assertSessionHasNoErrors();

    $transfer->refresh();
    expect($transfer->status)->toBe('accepted')
        ->and($transfer->held_at)->not->toBeNull()
        ->and(Notification::where('user_id', $this->admin->id)->where('title', 'Transfer to check: device payments')->exists())->toBeTrue();

    // Held: the cooling-off passing isn't enough.
    $this->travel(PropertyTransfer::COOLING_OFF_HOURS * 60 + 1)->minutes();
    $this->artisan('transfers:complete');
    expect($this->property->fresh()->user_id)->toBe($this->owner->id);

    // TenaFi checks the new owner and releases it.
    $this->actingAs($this->admin)->post(route('admin.transfers.release', $transfer))->assertSessionHasNoErrors();
    $this->artisan('transfers:complete');

    $agreement = $this->agreement->fresh();
    expect($this->property->fresh()->user_id)->toBe($this->newOwner->id)
        ->and($agreement->user_id)->toBe($this->newOwner->id)
        ->and($agreement->status)->toBe('active')
        ->and($agreement->instalments()->count())->toBe(12) // same schedule
        ->and($agreement->events()->first()->type)->toBe('transferred')
        ->and($transfer->fresh()->moved['devices_taken_over'])->toBe(1);
});

it('refuses to go ahead if more is owed than when it was offered', function () {
    offerProperty(['device_takeover' => true]);
    $transfer = PropertyTransfer::firstOrFail();
    DeviceAgreement::record([
        'user_id' => $this->owner->id, 'property_id' => $this->property->id, 'device_type' => 'outdoor',
        'price_kes' => 48000, 'monthly_kes' => 5500, 'months' => 12, 'starts_on' => now()->toDateString(),
    ]);

    $this->actingAs($this->newOwner)->post(route('host.transfers.accept', $transfer), ['takeover' => true])->assertSessionHasErrors('transfer');
    expect($transfer->fresh()->status)->toBe('pending');
});

it('lets a paid-off device move with the property without any take-over', function () {
    $this->agreement->changeStatus('paid_off', 'Paid off in full', $this->admin);

    offerProperty()->assertSessionHasNoErrors();
    expect(PropertyTransfer::firstOrFail()->device_takeover)->toBeFalse();
});
