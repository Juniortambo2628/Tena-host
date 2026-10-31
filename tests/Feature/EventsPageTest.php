<?php

use App\Mail\SignupAlertMail;
use App\Models\LandingPage;
use App\Models\LandingSection;
use App\Models\Registration;
use App\Models\Setting;
use App\Services\Cms\PageBlueprint;
use App\Support\TrialSlots;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

/** The live site before /events: no events page. */
function withoutEventsPage(): void
{
    LandingSection::whereHas('page', fn ($q) => $q->where('slug', 'events'))->delete();
    LandingPage::where('slug', 'events')->delete();
    LandingPage::clearCache();
}

it('adds /events hidden, then switches it on with the homepage copy', function () {
    withoutEventsPage();
    (require database_path('migrations/2026_10_31_000004_events_page.php'))->up();

    $this->get('/events')->assertNotFound();

    $this->artisan('tenafi:homepage-copy')->assertSuccessful();

    $this->get('/events')->assertOk()->assertInertia(fn ($page) => $page
        ->where('page.slug', 'events')
        ->where('sections', fn ($s) => collect($s)->pluck('section_key')->all() === array_keys(PageBlueprint::definitions()['events'])));
});

it('points every Events link at /events', function () {
    $copy = json_encode(PageBlueprint::definitions(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    expect($copy)->not->toContain('/#events')->not->toContain('TODO(events page)')
        ->and($copy)->toContain('"href":"/events"')
        ->and($copy)->toContain('/events#enquire');
});

it('saves a quote request as an event lead that does not take a trial slot', function () {
    Mail::fake();
    Setting::setValue('signup_alert_emails', 'team@example.com');
    Setting::setValue('trial_slots_total', '50');

    $this->postJson('/api/signups', [
        'type' => 'event',
        'firstName' => 'Achieng',
        'phone' => '0722 000 111',
        'eventName' => 'Kona Design Week',
        'eventType' => 'Conference or expo',
        'eventDate' => '2026-12-05',
        'attendees' => '500 to 2,000',
        'area' => 'Westlands',
        'consent' => true,
        'consentText' => 'I agree...',
        'submittedAt' => '2026-10-31T08:00:00Z',
        'source' => '/events',
    ])->assertCreated();

    $lead = Registration::sole();
    expect($lead->type)->toBe('event')
        ->and($lead->source_page)->toBe('events')
        ->and($lead->answers['eventName'])->toBe('Kona Design Week')
        ->and($lead->location)->toBe('Westlands')
        ->and(TrialSlots::summary()['taken'])->toBe(0);
    Mail::assertSent(SignupAlertMail::class);
});

it('keeps the copy rules on /events', function () {
    $events = PageBlueprint::definitions()['events'];
    $copy = json_encode($events, JSON_UNESCAPED_UNICODE);

    expect($events['worth_calculator']['content']['mode'])->toBe('events')
        ->and($events['signup']['content']['signup_type'])->toBe('event')
        ->and($copy)->toContain('Example')
        ->and($copy)->not->toContain('KICC');
});
