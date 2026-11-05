<?php

use App\Models\LandingPage;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function publicFeatureStatus(): array
{
    $content = LandingPage::siteSections()['feature_status']['content'] ?? [];
    $status = [];
    foreach ($content as $key => $value) {
        if (preg_match('/^items\.(\d+)\.key$/', $key, $m)) {
            $status[strip_tags($value)] = $content["items.{$m[1]}.status"] ?? null;
        }
    }

    return $status;
}

it('keeps the event features "Coming soon" while event accounts are switched off', function () {
    expect(publicFeatureStatus())->toMatchArray([
        'event_sponsors' => 'coming_soon',
        'event_question' => 'coming_soon',
        'event_tagging' => 'coming_soon',
        'tena_direct' => 'coming_soon',
    ]);
});

it('drops their badges once Admin -> Settings switches event accounts on, and only theirs', function () {
    Setting::setValue('event_accounts_enabled', '1', 'general', 'boolean');

    expect(publicFeatureStatus())->toMatchArray([
        'event_sponsors' => 'live',
        'event_question' => 'live',
        'event_tagging' => 'live',
        'tena_direct' => 'coming_soon',
        'vip_wifi' => 'coming_soon',
    ]);

    // The page sends that status to the browser, so the badges go there too.
    $this->get('/events')->assertInertia(fn ($page) => $page->where('site.feature_status.content', fn ($content) => collect($content)
        ->filter(fn ($v, $k) => str_ends_with($k, '.key') && strip_tags($v) === 'event_sponsors')
        ->keys()->every(fn ($k) => $content[str_replace('.key', '.status', $k)] === 'live')));
});
