<?php

use App\Models\Event;
use App\Models\Guest;
use App\Models\Property;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    Setting::setValue('billing_enabled', 'disabled', 'billing', 'string');
    $this->travelTo(Carbon::parse('2026-12-05 12:00:00'));
    $this->organiser = User::factory()->host()->create(['account_type' => User::ACCOUNT_EVENT, 'account_profile' => 'venue']);
    $this->hall = Property::factory()->create(['user_id' => $this->organiser->id, 'name' => 'Expo Hall']);
    $this->garden = Property::factory()->create(['user_id' => $this->organiser->id, 'name' => 'Garden']);
});

function designWeek(array $overrides = []): Event
{
    return Event::create(array_merge([
        'user_id' => test()->organiser->id, 'property_id' => test()->hall->id, 'name' => 'Kona Design Week',
        'starts_on' => '2026-12-04', 'ends_on' => '2026-12-06', 'question' => 'Where are you coming from?',
    ], $overrides));
}

it('is for event accounts only', function () {
    $business = User::factory()->host()->create(['account_type' => User::ACCOUNT_BUSINESS]);

    $this->actingAs($business)->get(route('host.events.index'))->assertNotFound();
    $this->actingAs($this->organiser)->get(route('host.events.index'))->assertOk();
});

it('lets an event account create an event at one of its venues', function () {
    $this->actingAs($this->organiser)->post(route('host.events.store'), [
        'name' => 'Kona Design Week', 'property_id' => $this->hall->id, 'starts_on' => '2026-12-04', 'ends_on' => '2026-12-06',
        'question' => 'Where are you coming from?', 'sponsor' => ['name' => 'Kahawa Co', 'message' => '2-for-1 coffee', 'url' => ''],
    ])->assertSessionHasNoErrors();

    $event = Event::sole();
    expect([$event->user_id, $event->property_id, $event->status()])->toBe([$this->organiser->id, $this->hall->id, 'live'])
        ->and($event->sponsor)->toBe(['name' => 'Kahawa Co', 'message' => '2-for-1 coffee']);
});

it('checks the venue and the dates', function () {
    $theirs = Property::factory()->create();

    $this->actingAs($this->organiser)->post(route('host.events.store'), [
        'name' => 'X', 'property_id' => $theirs->id, 'starts_on' => '2026-12-06', 'ends_on' => '2026-12-04',
    ])->assertSessionHasErrors(['property_id', 'ends_on']);
});

it('lists events as upcoming, live and past', function () {
    designWeek();
    designWeek(['name' => 'Year-end gala', 'starts_on' => '2026-12-20', 'ends_on' => '2026-12-20']);
    designWeek(['name' => 'Launch night', 'property_id' => $this->garden->id, 'starts_on' => '2026-03-01', 'ends_on' => '2026-03-01']);

    $this->actingAs($this->organiser)->get(route('host.events.index'))
        ->assertInertia(fn ($page) => $page->component('Host/Events/Index')
            ->where('events', fn ($events) => collect($events)->pluck('status', 'name')->all() === ['Year-end gala' => 'upcoming', 'Kona Design Week' => 'live', 'Launch night' => 'past'])
            ->has('venues', 2));
});

it('shows who came, new or returning, and their answers', function () {
    $event = designWeek();
    $regular = Guest::factory()->create(['property_id' => $this->hall->id, 'created_at' => '2026-06-01', 'marketing_opt_in' => true]);
    $newcomer = Guest::factory()->create(['property_id' => $this->hall->id, 'created_at' => '2026-12-04 10:00', 'marketing_opt_in' => false]);
    $event->attendees()->attach($regular->id, ['days' => ['2026-12-04', '2026-12-05'], 'answer' => 'Westlands', 'first_seen_at' => '2026-12-04 09:00']);
    $event->attendees()->attach($newcomer->id, ['days' => ['2026-12-04'], 'first_seen_at' => '2026-12-04 10:00']);

    $this->actingAs($this->organiser)->get(route('host.events.show', $event))
        ->assertInertia(fn ($page) => $page->component('Host/Events/Show')
            ->where('stats', ['attendees' => 2, 'returning' => 1, 'optedIn' => 1, 'answered' => 1])
            ->has('attendees.data', 2)
            ->where('attendees.data', fn ($rows) => collect($rows)->firstWhere('id', $regular->id)['returning'] === true
                && collect($rows)->firstWhere('id', $regular->id)['days'] === 2
                && collect($rows)->firstWhere('id', $regular->id)['answer'] === 'Westlands'));
});

it('keeps managers to their venues and others out', function () {
    $atHall = designWeek();
    $atGarden = designWeek(['name' => 'Garden party', 'property_id' => $this->garden->id]);
    $manager = User::factory()->host()->create(['account_type' => User::ACCOUNT_EVENT, 'team_owner_id' => $this->organiser->id, 'team_role' => 'manager']);
    $manager->staffProperties()->sync([$this->garden->id]);

    $this->actingAs($manager)->get(route('host.events.index'))
        ->assertInertia(fn ($page) => $page->has('events', 1)->where('events.0.name', 'Garden party'));
    $this->actingAs($manager)->get(route('host.events.show', $atHall))->assertForbidden();
    $this->actingAs($manager)->get(route('host.events.show', $atGarden))->assertOk();

    $stranger = User::factory()->host()->create(['account_type' => User::ACCOUNT_EVENT]);
    $this->actingAs($stranger)->delete(route('host.events.destroy', $atHall))->assertForbidden();
});

it('edits and deletes an event', function () {
    $event = designWeek();

    $this->actingAs($this->organiser)->put(route('host.events.update', $event), [
        'name' => 'Kona Design Week 2026', 'property_id' => $this->hall->id, 'starts_on' => '2026-12-04', 'ends_on' => '2026-12-07', 'question' => '',
    ])->assertSessionHasNoErrors();
    expect([$event->fresh()->name, $event->fresh()->ends_on->toDateString(), $event->fresh()->question])->toBe(['Kona Design Week 2026', '2026-12-07', null]);

    $this->actingAs($this->organiser)->delete(route('host.events.destroy', $event))->assertRedirect(route('host.events.index'));
    expect(Event::count())->toBe(0);
});
