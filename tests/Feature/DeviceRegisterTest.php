<?php

use App\Models\DeviceAgreement;
use App\Models\Property;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->client = User::factory()->host()->create();
    $this->site = Property::factory()->create(['user_id' => $this->client->id]);
});

function recordDevice(array $overrides = []): DeviceAgreement
{
    test()->actingAs(test()->admin)->post(route('admin.devices.store'), $overrides + [
        'user_id' => test()->client->id,
        'property_id' => test()->site->id,
        'device_type' => 'pro',
        'serial_number' => 'SN-0001',
        'mac_address' => 'AA:BB:CC:DD:EE:FF',
        'price_kes' => 42000,
        'monthly_kes' => 5000,
        'months' => 12,
        'starts_on' => '2026-01-31',
    ])->assertSessionHasNoErrors();

    return DeviceAgreement::latest('id')->firstOrFail();
}

it('keeps the device register to admins, and in the menu only once switched on', function () {
    $this->actingAs($this->client)->get(route('admin.devices.index'))->assertForbidden();

    $this->actingAs($this->admin)->get(route('admin.devices.index'))->assertOk()
        ->assertInertia(fn ($page) => $page->component('Admin/Devices/Index')->where('enabled', false)->where('auth.deviceRegister', false));

    Setting::setValue('device_financing_enabled', '1');
    $this->actingAs($this->admin)->get(route('admin.devices.index'))
        ->assertInertia(fn ($page) => $page->where('enabled', true)->where('auth.deviceRegister', true));
});

it('records a device with one instalment a month from the installation day', function () {
    $agreement = recordDevice();

    expect($agreement->user_id)->toBe($this->client->id)
        ->and($agreement->mac_address)->toBe('aa:bb:cc:dd:ee:ff')
        ->and($agreement->status)->toBe('active')
        ->and($agreement->instalments)->toHaveCount(12)
        ->and($agreement->instalments->first()->due_on->toDateString())->toBe('2026-01-31')
        // No overflow: the second instalment falls at the end of February, not in March.
        ->and($agreement->instalments[1]->due_on->toDateString())->toBe('2026-02-28')
        ->and($agreement->balance())->toBe(60000)
        ->and($agreement->events()->first()->type)->toBe('recorded');
});

it('refuses a site that belongs to another client', function () {
    $other = Property::factory()->create(['user_id' => User::factory()->host()->create()->id]);

    $this->actingAs($this->admin)->post(route('admin.devices.store'), [
        'user_id' => $this->client->id, 'property_id' => $other->id, 'device_type' => 'lite',
        'price_kes' => 25000, 'monthly_kes' => 3000, 'months' => 12, 'starts_on' => '2026-11-01',
    ])->assertSessionHasErrors('property_id');

    expect(DeviceAgreement::count())->toBe(0);
});

it('records part and full payments, shows days overdue, and settles the device after the last one', function () {
    $agreement = recordDevice(['months' => 2]);
    [$first, $second] = $agreement->instalments->all();

    expect($agreement->daysOverdue(Carbon::parse('2026-02-10')))->toBe(10);

    $this->actingAs($this->admin)->post(route('admin.devices.pay', [$agreement, $first]), ['amount_kes' => 2000, 'reference' => 'QWE123'])->assertSessionHasNoErrors();
    expect($first->fresh()->status)->toBe('partly_paid')->and($agreement->fresh()->load('instalments')->balance())->toBe(8000);

    // More than is left is refused.
    $this->actingAs($this->admin)->post(route('admin.devices.pay', [$agreement, $first]), ['amount_kes' => 9000])->assertSessionHasErrors('amount_kes');

    $this->actingAs($this->admin)->post(route('admin.devices.pay', [$agreement, $first]), ['amount_kes' => 3000]);
    $this->actingAs($this->admin)->post(route('admin.devices.pay', [$agreement, $second]), ['amount_kes' => 5000, 'reference' => 'RTY456']);

    $agreement->refresh()->load('instalments');
    expect($agreement->status)->toBe('paid_off')
        ->and($agreement->paid_off_at)->not->toBeNull()
        ->and($agreement->balance())->toBe(0)
        ->and($agreement->events()->pluck('type')->all())->toContain('payment', 'paid_off');
});

it('changes the status with a note in the log', function () {
    $agreement = recordDevice();

    $this->actingAs($this->admin)->put(route('admin.devices.status', $agreement), ['status' => 'suspended', 'note' => 'Two months unpaid'])->assertSessionHasNoErrors();
    $this->actingAs($this->admin)->put(route('admin.devices.status', $agreement), ['status' => 'nonsense'])->assertSessionHasErrors('status');

    $event = $agreement->events()->first();
    expect($agreement->fresh()->status)->toBe('suspended')
        ->and($event->type)->toBe('status')
        ->and($event->note)->toBe('Two months unpaid')
        // MySQL may store JSON keys in its own order, so compare without order.
        ->and($event->meta)->toEqual(['from' => 'active', 'to' => 'suspended'])
        ->and($event->user_id)->toBe($this->admin->id);
});

it('refuses a payment against another device’s instalment', function () {
    $one = recordDevice();
    $two = recordDevice(['serial_number' => 'SN-0002']);

    $this->actingAs($this->admin)->post(route('admin.devices.pay', [$one, $two->instalments->first()]), ['amount_kes' => 100])->assertNotFound();
});
