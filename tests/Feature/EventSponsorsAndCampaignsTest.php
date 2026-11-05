<?php

use App\Models\AccessPoint;
use App\Models\Campaign;
use App\Models\Event;
use App\Models\Guest;
use App\Models\Property;
use App\Models\Setting;
use App\Models\User;
use App\Services\CampaignDispatcher;
use App\Services\Unifi\UnifiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

beforeEach(function () {
    Setting::setValue('billing_enabled', 'disabled', 'billing', 'string');
    $this->travelTo(Carbon::parse('2026-12-05 10:00:00'));
    $this->organiser = User::factory()->host()->create(['account_type' => User::ACCOUNT_EVENT]);
    $this->hall = Property::factory()->create(['user_id' => $this->organiser->id, 'name' => 'Kona Expo Hall']);
    AccessPoint::factory()->create(['property_id' => $this->hall->id, 'mac_address' => '11:22:33:44:55:66']);
    $this->event = Event::create([
        'user_id' => $this->organiser->id, 'property_id' => $this->hall->id, 'name' => 'Kona Design Week',
        'starts_on' => '2026-12-04', 'ends_on' => '2026-12-06', 'question' => 'Where are you coming from?',
        'sponsor' => ['name' => 'Kahawa Co', 'message' => '2-for-1 coffee', 'url' => 'https://example.com/offer'],
    ]);
    $this->mock(UnifiService::class, function ($mock) {
        $mock->shouldReceive('authorizeGuest')->andReturnTrue();
        $mock->shouldReceive('normalizeMac')->andReturnUsing(fn ($m) => strtolower($m));
    });
});

function attend(string $name, ?string $answer, array $guest = []): Guest
{
    $g = Guest::factory()->create(['property_id' => test()->hall->id, 'first_name' => $name, 'consented_at' => now(), 'marketing_opt_in' => true] + $guest);
    test()->event->recordAttendance($g, $answer);

    return $g;
}

it('counts sponsor views on the WiFi page and taps on their link', function () {
    $this->get('/portal?ap=11:22:33:44:55:66&id=aa:bb:cc:dd:ee:ff')->assertOk();
    $this->get('/portal?ap=11:22:33:44:55:66&id=aa:bb:cc:dd:ee:01')->assertOk();
    $this->get(route('portal.sponsor', $this->event))->assertRedirect('https://example.com/offer');

    expect($this->event->sponsorReport())->toMatchArray(['views' => 2, 'clicks' => 1]);
});

it('doesn’t count pages without a real device or an event without a sponsor link', function () {
    $this->get('/portal?ap=11:22:33:44:55:66')->assertOk(); // no device MAC: not a guest's phone
    $this->event->update(['sponsor' => ['name' => 'Kahawa Co']]);
    $this->get(route('portal.sponsor', $this->event))->assertNotFound();

    expect($this->event->sponsorReport())->toMatchArray(['views' => 0, 'clicks' => 0]);
});

it('opens the sponsor report from a signed link only, with the top answers', function () {
    attend('Joy', 'Westlands');
    attend('Kevin', 'westlands ');
    attend('Amani', 'Kilimani');
    attend('Wanjiru', null);

    $this->get(route('events.sponsor-report', $this->event))->assertForbidden();

    $this->get(URL::signedRoute('events.sponsor-report', $this->event))->assertOk()
        ->assertSee('Kahawa Co at Kona Design Week')
        ->assertSee('Answered the question')
        ->assertSee('Westlands')
        ->assertSee('noindex', false);

    expect($this->event->sponsorReport())->toMatchArray(['attendees' => 4, 'answered' => 3])
        ->and($this->event->sponsorReport()['topAnswers'][0])->toBe(['answer' => 'Westlands', 'count' => 2]);
});

it('gives the organiser the report link and a way to message attendees', function () {
    $this->actingAs($this->organiser)->get(route('host.events.show', $this->event))
        ->assertInertia(fn ($page) => $page->where('sponsorReport.views', 0)
            ->where('sponsorReport.url', fn ($url) => str_contains($url, 'signature='))
            ->where('messageUrl', route('host.marketing.builder', ['event' => $this->event->id])));

    $this->actingAs($this->organiser)->get(route('host.marketing.builder', ['event' => $this->event->id]))
        ->assertInertia(fn ($page) => $page->where('preselectEvent', $this->event->id)->has('events', 1));
});

it('aims a campaign at one event’s attendees', function () {
    $came = attend('Joy', 'Westlands');
    Guest::factory()->create(['property_id' => $this->hall->id, 'consented_at' => now(), 'marketing_opt_in' => true]); // never came

    $campaign = new Campaign(['user_id' => $this->organiser->id, 'type' => 'whatsapp', 'target_audience' => 'all_guests', 'audience_event_id' => $this->event->id]);

    expect(app(CampaignDispatcher::class)->audienceQuery($campaign)->pluck('id')->all())->toBe([$came->id]);

    $this->actingAs($this->organiser)->getJson(route('host.marketing.estimate', ['type' => 'whatsapp', 'audience_event_id' => $this->event->id]))
        ->assertOk()->assertJsonPath('count', 1);
});

it('only lets an organiser target their own events', function () {
    $other = Event::create([
        'user_id' => User::factory()->host()->create(['account_type' => User::ACCOUNT_EVENT])->id,
        'property_id' => Property::factory()->create()->id, 'name' => 'Someone else’s', 'starts_on' => '2026-12-01', 'ends_on' => '2026-12-01',
    ]);

    $this->actingAs($this->organiser)->getJson(route('host.marketing.estimate', ['type' => 'whatsapp', 'audience_event_id' => $other->id]))
        ->assertJsonValidationErrors('audience_event_id');
});
