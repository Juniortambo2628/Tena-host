<?php

use App\Models\LandingSection;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function liveSection(string $slug, string $key): LandingSection
{
    return LandingSection::where('section_key', $key)->whereHas('page', fn ($q) => $q->where('slug', $slug))->firstOrFail();
}

function setLive(LandingSection $section, string $key, string $value): void
{
    $section->contents()->updateOrCreate(['content_key' => $key], ['value' => $value, 'type' => 'html']);
}

function liveValue(LandingSection $section, string $key): ?string
{
    return $section->contents()->where('content_key', $key)->value('value');
}

function runLiveTrialMigration(): void
{
    (require database_path('migrations/2026_10_30_000005_live_free_trial_copy.php'))->up();
}

it('replaces the old Founding 20 copy on the live pages but keeps what the admin rewrote', function () {
    $banner = liveSection('hosts', 'cta_banner__founding');
    $pricing = liveSection('hosts', 'pricing');
    $signup = liveSection('business', 'signup');
    $faq = liveSection('hosts', 'faq');

    // The live site as it might be today.
    setLive($banner, 'badge', 'Founding 20');
    setLive($banner, 'body', '<p>10 short-term rental operators get TenaFi installed from December 2026, in time for the high season, with the first two months free. In return: honest feedback, results we can share, and referrals if you love it.</p>');
    setLive($banner, 'buttons.0.label', 'Apply for a spot');
    setLive($banner, 'title', 'Join our Founding 20 this month!');          // edited, but still the old offer
    setLive($pricing, 'subtitle', 'Free for all of December. No card needed.'); // Glen already rewrote it
    setLive($signup, 'subtitle', 'Apply for the Founding 20 for business owners. First two months free.');
    setLive($faq, 'items.1.answer', 'A different answer the admin moved here.');

    runLiveTrialMigration();

    expect(liveValue($banner, 'badge'))->toBe('Free trial')
        ->and(liveValue($banner, 'body'))->toContain('Installs start 16 November')->not->toContain('Founding')
        ->and(liveValue($banner, 'buttons.0.label'))->toBe('Book my free trial')
        ->and(liveValue($banner, 'title'))->toBe('Try TenaFi free for the whole of December.')
        ->and(liveValue($pricing, 'subtitle'))->toBe('Free for all of December. No card needed.')
        ->and(liveValue($signup, 'subtitle'))->toContain('Book a free trial for your business')
        ->and(liveValue($faq, 'items.1.answer'))->toBe('A different answer the admin moved here.');
});

it('leaves no Founding 20 offer anywhere on the public pages and is safe to run twice', function () {
    foreach (['home', 'hosts', 'business'] as $slug) {
        setLive(liveSection($slug, 'cta_banner__founding'), 'badge', 'Founding 20');
    }
    runLiveTrialMigration();
    runLiveTrialMigration();

    foreach (['/', '/hosts', '/business'] as $url) {
        $this->get($url)->assertOk()->assertInertia(fn ($page) => $page
            ->where('sections', fn ($sections) => ! str_contains(json_encode($sections), 'Founding 20')));
    }
});
