<?php

use App\Models\LandingContent;
use App\Models\LandingPage;
use App\Models\Registration;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

function demoContent(string $slug, string $section, string $key): ?LandingContent
{
    return LandingContent::whereHas('section', fn ($q) => $q->where('section_key', $section)->whereHas('page', fn ($p) => $p->where('slug', $slug)))
        ->where('content_key', $key)->first();
}

/** The live pages as they were during the free trial, with one admin edit to keep. */
function liveTrialCopy(): void
{
    $set = fn (string $slug, string $section, string $key, string $value) => demoContent($slug, $section, $key)?->update(['value' => $value]);
    $set('home', 'hero', 'cta_primary', 'Book my free trial');
    $set('home', 'hero', 'show_slots', '1');
    $set('home', 'cta_banner__founding', 'title', 'Try TenaFi free for the whole of December.');
    $set('home', 'faq', 'items.4.question', 'What happens after the free trial?');
    $set('hosts', 'pricing', 'footnote', 'Free trial: installs from 16 November, and the rest of November and all of December are free. Then month to month; cancel with 30 days’ notice. Glen’s own sentence.');
    $set('business', 'signup', 'submit_label', 'Book my free trial');
    $set(LandingPage::SITE, 'announcement', 'text', 'Free trial for December · Installs from 16 November · {taken} of {total} slots taken');
    // A field with no blueprint wording: listed for a person to edit, never guessed.
    $set('home', 'faq', 'subtitle', 'Start with a free trial and see.');
    $fields = collect(json_decode(demoContent('hosts', 'signup', 'steps.0.fields')->value, true))->reject(fn ($f) => $f['key'] === 'demoType')->values();
    demoContent('hosts', 'signup', 'steps.0.fields')->update(['value' => json_encode($fields)]);
    LandingPage::clearCache();
}

it('replaces the free trial with Book a demo, keeping the admin’s other wording, and can restore', function () {
    Storage::fake('local');
    liveTrialCopy();

    $this->artisan('tenafi:book-a-demo --dry-run')
        ->expectsOutputToContain('[home/hero] cta_primary')
        ->expectsOutputToContain('new question on the details step: How would you like your demo?')
        ->expectsOutputToContain('[still mentions the trial] home/faq subtitle')
        ->expectsOutputToContain('Dry run: nothing was saved')
        ->assertSuccessful();
    expect(demoContent('home', 'hero', 'cta_primary')->value)->toBe('Book my free trial');

    $this->artisan('tenafi:book-a-demo')->expectsOutputToContain('Backup saved')->assertSuccessful();

    expect(demoContent('home', 'hero', 'cta_primary')->value)->toBe('Book a demo')
        ->and(demoContent('home', 'hero', 'show_slots')->value)->toBe('0')
        ->and(demoContent('home', 'cta_banner__founding', 'title')->value)->toBe('See TenaFi working for your place.')
        ->and(demoContent('home', 'faq', 'items.4.question')->value)->toBe('What happens in a demo?')
        ->and(demoContent('hosts', 'pricing', 'footnote')->value)->toBe('Month to month; cancel with 30 days’ notice. Glen’s own sentence.')
        ->and(demoContent('business', 'signup', 'submit_label')->value)->toBe('Book my demo')
        ->and(demoContent(LandingPage::SITE, 'announcement', 'text')->value)->not->toContain('trial')
        ->and(demoContent('home', 'faq', 'subtitle')->value)->toBe('Start with a free trial and see.')
        ->and(collect(json_decode(demoContent('hosts', 'signup', 'steps.0.fields')->value, true))->pluck('key'))->toContain('demoType');

    $backup = basename(Storage::disk('local')->files('cms-backups')[0]);
    $this->artisan("tenafi:book-a-demo --restore={$backup}")->assertSuccessful();
    expect(demoContent('home', 'hero', 'cta_primary')->value)->toBe('Book my free trial')
        ->and(demoContent('hosts', 'pricing', 'footnote')->value)->toContain('Free trial: installs from 16 November');
});

it('ships Book a demo wording and no slot counter on a fresh install', function () {
    expect(demoContent('home', 'hero', 'cta_primary')->value)->toBe('Book a demo')
        ->and(demoContent('hosts', 'signup', 'submit_label')->value)->toBe('Book my demo')
        ->and(LandingContent::where('content_key', 'show_slots')->where('value', '1')->count())->toBe(0)
        ->and(LandingContent::whereHas('section', fn ($q) => $q->where('section_key', '!=', 'trial_slots'))->where('value', 'like', '%free trial%')->count())->toBe(0);
});

it('stores how they would like their demo', function () {
    Mail::fake();

    $this->postJson(route('signups.store'), [
        'type' => 'business', 'firstName' => 'Otieno', 'phone' => '+254 722 000 111', 'businessName' => 'Kahawa House',
        'plan' => 'starter', 'demoType' => 'visit', 'consent' => true,
    ])->assertCreated();

    expect(Registration::first()->answers['demoType'])->toBe('visit');
});
