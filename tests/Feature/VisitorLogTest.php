<?php

use App\Models\AccessPoint;
use App\Models\Guest;
use App\Models\Property;
use App\Models\Setting;
use App\Models\User;
use App\Models\VisitorVisit;
use App\Services\Unifi\UnifiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    Setting::setValue('billing_enabled', 'disabled', 'billing', 'string');
    Setting::setValue('visitor_log_enabled', '1', 'general', 'boolean');
    $this->travelTo(Carbon::parse('2026-11-10 09:00:00'));
    $this->company = User::factory()->host()->create(['account_type' => User::ACCOUNT_BUSINESS]);
    $this->office = Property::factory()->create(['user_id' => $this->company->id, 'name' => 'Acme HQ', 'visitor_log' => true]);
    AccessPoint::factory()->create(['property_id' => $this->office->id, 'mac_address' => '11:22:33:44:55:66']);
    $this->mock(UnifiService::class, function ($mock) {
        $mock->shouldReceive('authorizeGuest')->andReturnTrue();
        $mock->shouldReceive('normalizeMac')->andReturnUsing(fn ($m) => strtolower($m));
    });
});

function visitorSignIn(array $overrides = [])
{
    return test()->post('/portal/connect', array_merge([
        'id' => 'aa:bb:cc:dd:ee:ff', 'ap' => '11:22:33:44:55:66',
        'first_name' => 'Joy', 'phone' => '0712 345 678', 'consent' => '1', 'visiting' => 'Finance team',
    ], $overrides));
}

it('asks who the visitor is here to see and logs the visit', function () {
    $this->get('/portal?ap=11:22:33:44:55:66&id=aa:bb:cc:dd:ee:ff')->assertOk()
        ->assertSee('Who are you here to see?')->assertSee('name="visiting"', false);

    visitorSignIn(['visiting' => ''])->assertStatus(422)->assertSee('Please say who you’re here to see.');
    expect(Guest::count())->toBe(0);

    visitorSignIn();

    $visit = VisitorVisit::sole();
    expect($visit)->name->toBe('Joy')->visiting->toBe('Finance team')->contact->toBe('+254712345678')
        ->and($visit->guest_id)->toBe(Guest::sole()->id)
        ->and($visit->left_at)->toBeNull();
});

it('asks a returning visitor too, keeping one row while they are still in', function () {
    visitorSignIn();

    // Reconnecting later the same day: the one-tap page asks again, and the open visit is kept.
    $this->get('/portal?ap=11:22:33:44:55:66&id=aa:bb:cc:dd:ee:ff')->assertSee('One tap to reconnect')->assertSee('name="visiting"', false);
    $this->post('/portal/connect', ['id' => 'aa:bb:cc:dd:ee:ff', 'ap' => '11:22:33:44:55:66'])->assertStatus(422);
    $this->post('/portal/connect', ['id' => 'aa:bb:cc:dd:ee:ff', 'ap' => '11:22:33:44:55:66', 'visiting' => 'Amani in HR']);

    expect(VisitorVisit::count())->toBe(1)->and(VisitorVisit::sole()->visiting)->toBe('Amani in HR');

    // The next day is a new visit.
    $this->travelTo(Carbon::parse('2026-11-11 10:00:00'));
    $this->post('/portal/connect', ['id' => 'aa:bb:cc:dd:ee:ff', 'ap' => '11:22:33:44:55:66', 'visiting' => 'Finance team']);
    expect(VisitorVisit::count())->toBe(2);
});

it('leaves the sign-in as before when the site or the switch is off', function () {
    $this->office->update(['visitor_log' => false]);
    $this->get('/portal?ap=11:22:33:44:55:66&id=aa:bb:cc:dd:ee:ff')->assertDontSee('name="visiting"', false);

    $this->office->update(['visitor_log' => true]);
    Setting::setValue('visitor_log_enabled', '0', 'general', 'boolean');
    $this->get('/portal?ap=11:22:33:44:55:66&id=aa:bb:cc:dd:ee:ff')->assertDontSee('name="visiting"', false);
    visitorSignIn(['visiting' => ''])->assertRedirect(); // on to the business homepage, as before

    expect(VisitorVisit::count())->toBe(0);
    $this->actingAs($this->company)->get(route('host.visitors.index'))->assertNotFound();
});

it('shows reception the day’s log, with walk-ins and sign-outs', function () {
    visitorSignIn();

    $this->actingAs($this->company)->get(route('host.dashboard'))
        ->assertInertia(fn ($page) => $page->where('auth.visitorLog', true));

    $this->actingAs($this->company)->post(route('host.visitors.store'), [
        'property_id' => $this->office->id, 'name' => 'Courier', 'visiting' => 'Front desk',
    ])->assertSessionHasNoErrors();

    $joy = VisitorVisit::where('name', 'Joy')->sole();
    $this->actingAs($this->company)->patch(route('host.visitors.leave', $joy))->assertRedirect();
    expect($joy->fresh()->left_at)->not->toBeNull();

    $this->actingAs($this->company)->get(route('host.visitors.index'))
        ->assertInertia(fn ($page) => $page->component('Host/Visitors/Index')
            ->has('visits', 2)
            ->where('visits.0.name', 'Courier')->where('visits.0.walk_in', true)
            ->where('visits.1.left_at', fn ($v) => $v !== null)
            ->where('isToday', true));

    // Another day's log is empty.
    $this->actingAs($this->company)->get(route('host.visitors.index', ['date' => '2026-11-09']))
        ->assertInertia(fn ($page) => $page->has('visits', 0)->where('isToday', false));
});

it('keeps each company to its own sites and visits', function () {
    $other = User::factory()->host()->create(['account_type' => User::ACCOUNT_BUSINESS]);
    $theirs = Property::factory()->create(['user_id' => $other->id, 'visitor_log' => true]);
    $visit = VisitorVisit::create(['property_id' => $theirs->id, 'name' => 'Theirs', 'visiting' => 'Someone', 'arrived_at' => now()]);

    $this->actingAs($this->company)->get(route('host.visitors.index'))
        ->assertInertia(fn ($page) => $page->has('visits', 0)->has('sites', 1));
    $this->actingAs($this->company)->patch(route('host.visitors.leave', $visit))->assertNotFound();
    $this->actingAs($this->company)->post(route('host.visitors.store'), [
        'property_id' => $theirs->id, 'name' => 'X', 'visiting' => 'Y',
    ])->assertSessionHasErrors('property_id');

    expect($visit->fresh()->left_at)->toBeNull();
});

it('lets the company turn the log on for a site', function () {
    $this->office->update(['visitor_log' => false]);

    $this->actingAs($this->company)->get(route('host.properties.edit', $this->office))
        ->assertInertia(fn ($page) => $page->where('visitorLogEnabled', true));
    $this->actingAs($this->company)->get(route('host.dashboard'))
        ->assertInertia(fn ($page) => $page->where('auth.visitorLog', false));

    $this->actingAs($this->company)->patch(route('host.properties.update', $this->office), ['name' => 'Acme HQ', 'visitor_log' => '1'])
        ->assertSessionHasNoErrors();

    expect($this->office->fresh()->visitor_log)->toBeTrue();
});
