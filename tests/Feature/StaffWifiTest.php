<?php

use App\Models\AccessPoint;
use App\Models\Event;
use App\Models\Guest;
use App\Models\Property;
use App\Models\Setting;
use App\Models\StaffConnection;
use App\Models\User;
use App\Services\Unifi\UnifiService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Setting::setValue('billing_enabled', 'disabled', 'billing', 'string');
    Setting::setValue('staff_wifi_enabled', '1', 'general', 'boolean');
    $this->company = User::factory()->host()->create(['account_type' => User::ACCOUNT_BUSINESS]);
    $this->office = Property::factory()->create([
        'user_id' => $this->company->id, 'name' => 'Acme HQ',
        'wifi_ssid' => 'Acme-Guest', 'staff_ssid' => 'Acme-Staff',
    ]);
    AccessPoint::factory()->create(['property_id' => $this->office->id, 'mac_address' => '11:22:33:44:55:66']);
    $this->mock(UnifiService::class, function ($mock) {
        $mock->shouldReceive('authorizeGuest')->andReturnTrue();
        $mock->shouldReceive('normalizeMac')->andReturnUsing(fn ($m) => strtolower($m));
    });
});

function staffPage(string $ssid = 'Acme-Staff')
{
    return test()->get('/portal?ap=11:22:33:44:55:66&id=aa:bb:cc:dd:ee:ff&ssid='.$ssid);
}

function staffSignIn(array $overrides = [])
{
    return test()->post('/portal/connect', array_merge([
        'id' => 'aa:bb:cc:dd:ee:ff', 'ap' => '11:22:33:44:55:66', 'ssid' => 'Acme-Staff',
        'staff_name' => 'Wanjiru', 'staff_id' => 'EMP-104',
    ], $overrides));
}

it('shows the staff sign-in on the staff network, not the guest form', function () {
    staffPage()->assertOk()
        ->assertSee('Staff sign-in')
        ->assertDontSee('{{', false)
        ->assertSee('name="staff_id"', false)
        ->assertSee('You won\'t get marketing messages', false)
        ->assertDontSee('name="phone"', false)
        ->assertDontSee('name="marketing_opt_in"', false);

    // The guest network at the same site is unchanged; the SSID match ignores case.
    staffPage('Acme-Guest')->assertOk()->assertSee('name="phone"', false)->assertDontSee('name="staff_id"', false);
    staffPage('acme-staff')->assertOk()->assertSee('name="staff_id"', false);
});

it('leaves every network on the guest page while the switch is off', function () {
    Setting::setValue('staff_wifi_enabled', '0', 'general', 'boolean');

    staffPage()->assertOk()->assertSee('name="phone"', false)->assertDontSee('name="staff_id"', false);
});

it('records a staff connection, never a guest or an event attendee', function () {
    $event = Event::create([
        'user_id' => $this->company->id, 'property_id' => $this->office->id, 'name' => 'Town hall',
        'starts_on' => today()->toDateString(), 'ends_on' => today()->toDateString(),
    ]);

    staffSignIn()->assertOk()->assertSee('staff WiFi at Acme HQ');

    expect(Guest::count())->toBe(0)
        ->and($event->attendees()->count())->toBe(0)
        ->and(StaffConnection::sole())->name->toBe('Wanjiru')->identifier->toBe('EMP-104')->visits->toBe(1);
});

it('counts the same person again instead of adding a row', function () {
    staffSignIn();
    $this->travel(1)->days();
    staffSignIn(['id' => '00:11:22:33:44:55', 'staff_name' => 'Wanjiru K']);

    expect(StaffConnection::count())->toBe(1)
        ->and(StaffConnection::sole())->visits->toBe(2)->name->toBe('Wanjiru K');
});

it('needs a name and an ID, and a work email when the site sets a domain', function () {
    staffSignIn(['staff_name' => '', 'staff_id' => ''])->assertStatus(422)->assertSee('Please add your name.');

    $this->office->update(['staff_email_domain' => 'acme.co.ke']);
    staffPage()->assertSee('placeholder="you@acme.co.ke"', false);
    staffSignIn(['staff_id' => 'wanjiru@gmail.com'])->assertStatus(422)->assertSee('Use your work email');
    expect(StaffConnection::count())->toBe(0);

    staffSignIn(['staff_id' => 'Wanjiru@Acme.co.ke'])->assertOk();
    expect(StaffConnection::count())->toBe(1);
});

it('lets the company set the staff network and see who signed in', function () {
    $this->actingAs($this->company)->get(route('host.properties.edit', $this->office))
        ->assertInertia(fn ($page) => $page->where('staffWifiEnabled', true));

    $this->actingAs($this->company)->patch(route('host.properties.update', $this->office), [
        'name' => 'Acme HQ', 'staff_ssid' => 'Acme-Team', 'staff_email_domain' => '@acme.co.ke',
    ])->assertSessionHasNoErrors();
    expect($this->office->fresh())->staff_ssid->toBe('Acme-Team')->staff_email_domain->toBe('@acme.co.ke');

    $this->actingAs($this->company)->patch(route('host.properties.update', $this->office), [
        'name' => 'Acme HQ', 'staff_email_domain' => 'not a domain',
    ])->assertSessionHasErrors('staff_email_domain');

    staffSignIn(['ssid' => 'Acme-Team', 'staff_id' => 'wanjiru@acme.co.ke']);

    $this->actingAs($this->company)->get(route('host.properties.show', $this->office))
        ->assertInertia(fn ($page) => $page->where('staffWifi.ssid', 'Acme-Team')
            ->where('staffWifi.today', 1)
            ->where('staffWifi.latest.0.name', 'Wanjiru'));
});

it('hides the staff list while the switch is off', function () {
    Setting::setValue('staff_wifi_enabled', '0', 'general', 'boolean');

    $this->actingAs($this->company)->get(route('host.properties.show', $this->office))
        ->assertInertia(fn ($page) => $page->where('staffWifi', null));
});
