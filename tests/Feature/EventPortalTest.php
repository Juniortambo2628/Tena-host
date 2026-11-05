<?php

use App\Models\AccessPoint;
use App\Models\Event;
use App\Models\Guest;
use App\Models\Property;
use App\Models\User;
use App\Services\Unifi\UnifiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-12-05 10:00:00'));
    $organiser = User::factory()->host()->create(['account_type' => User::ACCOUNT_EVENT]);
    $this->hall = Property::factory()->create(['user_id' => $organiser->id, 'name' => 'Kona Expo Hall']);
    AccessPoint::factory()->create(['property_id' => $this->hall->id, 'mac_address' => '11:22:33:44:55:66']);
    $this->event = Event::create([
        'user_id' => $organiser->id, 'property_id' => $this->hall->id, 'name' => 'Kona Design Week',
        'starts_on' => '2026-12-04', 'ends_on' => '2026-12-06', 'question' => 'Where are you coming from?',
        'sponsor' => ['name' => 'Kahawa Co', 'message' => '2-for-1 coffee at the café stand', 'url' => 'https://example.com/offer'],
    ]);
    $this->mock(UnifiService::class, function ($mock) {
        $mock->shouldReceive('authorizeGuest')->andReturnTrue();
        $mock->shouldReceive('normalizeMac')->andReturnUsing(fn ($m) => strtolower($m));
    });
});

function signIn(array $overrides = [])
{
    return test()->post('/portal/connect', array_merge([
        'id' => 'aa:bb:cc:dd:ee:ff', 'ap' => '11:22:33:44:55:66',
        'first_name' => 'Joy', 'phone' => '0712 345 678', 'consent' => '1',
    ], $overrides));
}

it('shows the running event’s welcome, question and sponsor on the WiFi page', function () {
    $this->get('/portal?ap=11:22:33:44:55:66&id=aa:bb:cc:dd:ee:ff')->assertOk()
        ->assertSee('Welcome to Kona Design Week')
        ->assertSee('Where are you coming from?')
        ->assertSee('name="event_answer"', false)
        ->assertSee('Brought to you by')
        ->assertSee('Kahawa Co')
        // The sponsor's link goes through TenaFi so taps are counted for their report.
        ->assertSee(route('portal.sponsor', $this->event), false);
});

it('uses the event’s own heading and message when set', function () {
    $this->event->update(['branding_json' => ['heading' => 'Design Week · Day 2', 'message' => 'Free WiFi all day.']]);

    $this->get('/portal?ap=11:22:33:44:55:66&id=aa:bb:cc:dd:ee:ff')->assertOk()
        ->assertSee('Design Week · Day 2')->assertSee('Free WiFi all day.');
});

it('adds each sign-in to the event, with the day and the answer', function () {
    signIn(['event_answer' => 'Westlands']);

    $guest = Guest::sole();
    $row = $this->event->attendees()->sole()->pivot;
    expect($row->days)->toBe(['2026-12-05'])->and($row->answer)->toBe('Westlands');

    // The same phone the next day: one row, two days, the answer kept.
    $this->travelTo(Carbon::parse('2026-12-06 09:00:00'));
    signIn(['event_answer' => '']);

    $row = $this->event->attendees()->sole()->pivot;
    expect($row->days)->toBe(['2026-12-05', '2026-12-06'])->and($row->answer)->toBe('Westlands')
        ->and($this->event->attendees()->count())->toBe(1)
        ->and(Guest::count())->toBe(1)
        ->and($guest->fresh()->events()->pluck('events.id')->all())->toBe([$this->event->id]);
});

it('counts a returning device’s one-tap reconnect too', function () {
    $regular = Guest::factory()->create(['property_id' => $this->hall->id, 'device_mac' => 'aa:bb:cc:dd:ee:ff', 'last_connected' => now()->subDays(30)]);

    $this->post('/portal/connect', ['id' => 'aa:bb:cc:dd:ee:ff', 'ap' => '11:22:33:44:55:66']);

    expect($this->event->attendees()->pluck('guests.id')->all())->toBe([$regular->id]);
});

it('leaves the page and sign-ins as before outside the event’s dates', function () {
    $this->travelTo(Carbon::parse('2026-12-10 10:00:00'));

    $this->get('/portal?ap=11:22:33:44:55:66&id=aa:bb:cc:dd:ee:ff')->assertOk()
        ->assertDontSee('Kona Design Week')->assertDontSee('name="event_answer"', false)->assertDontSee('Brought to you by');
    signIn();

    expect(Guest::count())->toBe(1)->and($this->event->attendees()->count())->toBe(0);
});
