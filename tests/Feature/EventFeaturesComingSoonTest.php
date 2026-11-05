<?php

use App\Models\LandingContent;
use App\Models\LandingPage;
use App\Models\LandingSection;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function eventsSection(string $key): LandingSection
{
    return LandingSection::whereHas('page', fn ($q) => $q->where('slug', 'events'))->where('section_key', $key)->firstOrFail();
}

function featureStatus(): array
{
    $section = LandingSection::whereHas('page', fn ($q) => $q->where('slug', LandingPage::SITE))->where('section_key', 'feature_status')->firstOrFail();
    $rows = $section->contents()->pluck('value', 'content_key');
    $status = [];
    foreach ($rows as $key => $value) {
        if (preg_match('/^items\.(\d+)\.key$/', $key, $m)) {
            $status[$value] = $rows["items.{$m[1]}.status"] ?? null;
        }
    }

    return $status;
}

/** The live site before this change: no event feature flags, rows not tied to them. */
function untagEventFeatures(): void
{
    $site = LandingSection::whereHas('page', fn ($q) => $q->where('slug', LandingPage::SITE))->where('section_key', 'feature_status')->firstOrFail();
    $keys = $site->contents()->where('content_key', 'like', 'items.%.key')->where('value', 'like', 'event_%')->pluck('content_key');
    foreach ($keys as $key) {
        $site->contents()->where('content_key', 'like', preg_replace('/\.key$/', '.%', $key))->delete();
    }

    LandingContent::where('content_key', 'like', '%.feature')->where('value', 'like', 'event_%')->update(['value' => '']);
    foreach (LandingContent::where('content_key', 'like', '%.features')->get() as $list) {
        $items = json_decode($list->value, true);
        if (is_array($items)) {
            $list->update(['value' => json_encode(array_map(fn ($i) => str_starts_with($i['feature'] ?? '', 'event_') ? ['feature' => ''] + $i : $i, $items))]);
        }
    }
    LandingPage::clearCache();
}

function runEventFeaturesMigration(): void
{
    (require database_path('migrations/2026_11_05_000001_event_features_coming_soon.php'))->up();
}

it('marks the event features the product doesn’t have yet as coming soon', function () {
    untagEventFeatures();
    expect(featureStatus())->not->toHaveKey('event_sponsors');

    runEventFeaturesMigration();

    expect(featureStatus())->toMatchArray([
        'event_sponsors' => 'coming_soon',
        'event_question' => 'coming_soon',
        'event_tagging' => 'coming_soon',
    ]);

    $plays = eventsSection('features__plays')->contents()->pluck('value', 'content_key');
    expect($plays['items.1.feature'])->toBe('event_sponsors')
        ->and($plays['items.2.feature'])->toBe('event_tagging')
        ->and($plays['items.0.feature'])->toBe('');

    $rows = eventsSection('detailed_features__events')->contents()->pluck('value', 'content_key');
    $attendees = collect(json_decode($rows['sections.0.features'], true))->pluck('feature', 'title');
    expect($attendees['Name, phone and email, plus one question of your choice'])->toBe('event_question')
        ->and($attendees['New and returning attendees, across all your events'])->toBe('event_tagging')
        ->and($attendees['Plus-ones and walk-ins counted, not just ticket buyers'])->toBe('');
});

it('leaves an admin’s choices alone and can run twice', function () {
    untagEventFeatures();
    runEventFeaturesMigration();

    // An admin switches sponsors on, and ties a row to another feature.
    $site = LandingSection::whereHas('page', fn ($q) => $q->where('slug', LandingPage::SITE))->where('section_key', 'feature_status')->firstOrFail();
    $key = $site->contents()->where('content_key', 'like', 'items.%.key')->where('value', 'event_sponsors')->value('content_key');
    $site->contents()->where('content_key', preg_replace('/\.key$/', '.status', $key))->update(['value' => 'live']);
    eventsSection('features__plays')->contents()->where('content_key', 'items.2.feature')->update(['value' => 'monthly_report']);

    runEventFeaturesMigration();

    expect(featureStatus()['event_sponsors'])->toBe('live')
        ->and(collect(featureStatus())->keys()->filter(fn ($k) => $k === 'event_question'))->toHaveCount(1)
        ->and(eventsSection('features__plays')->contents()->where('content_key', 'items.2.feature')->value('value'))->toBe('monthly_report');
});

it('ships the badges in the page definitions for new sites', function () {
    $this->get('/')->assertOk();
    expect(featureStatus())->toMatchArray(['event_sponsors' => 'coming_soon', 'event_question' => 'coming_soon', 'event_tagging' => 'coming_soon']);
});
