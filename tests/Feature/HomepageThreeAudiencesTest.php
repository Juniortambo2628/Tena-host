<?php

use App\Models\LandingPage;
use App\Models\LandingSection;
use App\Services\Cms\PageBlueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

// The copy commands save backups; keep them off the real disk.
beforeEach(fn () => Storage::fake('local'));

function homeKey(string $key): ?LandingSection
{
    return LandingSection::whereHas('page', fn ($q) => $q->where('slug', 'home'))->where('section_key', $key)->first();
}

/** The live homepage before this change: the old sections only, with some admin-edited copy. */
function oldLiveHomepage(): void
{
    foreach (['features__plays', 'detailed_features__home', 'comparison_table', 'credibility', 'faq'] as $key) {
        homeKey($key)?->delete();
    }
    LandingSection::whereHas('page', fn ($q) => $q->where('slug', LandingPage::SITE))->where('section_key', 'announcement')->delete();
    homeKey('hero')->contents()->where('content_key', 'title')->update(['value' => 'Glen’s live hero title']);
    homeKey('hero')->contents()->where('content_key', 'like', 'audiences.%')->delete();
    LandingPage::clearCache();
}

it('adds the new homepage sections hidden and leaves the live ones alone', function () {
    oldLiveHomepage();

    (require database_path('migrations/2026_10_31_000001_homepage_three_audiences.php'))->up();

    foreach (['features__plays', 'detailed_features__home', 'comparison_table', 'credibility', 'faq'] as $key) {
        expect(homeKey($key))->not->toBeNull()->and(homeKey($key)->is_active)->toBeFalse();
    }
    expect(homeKey('hero')->contents()->where('content_key', 'title')->value('value'))->toBe('Glen’s live hero title');
    $this->get('/')->assertInertia(fn ($page) => $page->where('sections', fn ($s) => ! collect($s)->contains('section_key', 'comparison_table')));
});

it('shows the copy diff on a dry run, then backs up, applies and can restore', function () {
    Storage::fake('local');
    oldLiveHomepage();
    (require database_path('migrations/2026_10_31_000001_homepage_three_audiences.php'))->up();

    $this->artisan('tenafi:homepage-copy --dry-run')
        ->expectsOutputToContain('[home/hero] title')
        ->expectsOutputToContain('Dry run: nothing was saved')
        ->assertSuccessful();
    expect(homeKey('hero')->contents()->where('content_key', 'title')->value('value'))->toBe('Glen’s live hero title')
        ->and(homeKey('faq')->is_active)->toBeFalse()
        ->and(Storage::disk('local')->files('cms-backups'))->toBeEmpty();

    $this->artisan('tenafi:homepage-copy')->expectsOutputToContain('Backup saved')->assertSuccessful();

    expect(homeKey('hero')->contents()->where('content_key', 'title')->value('value'))->toContain('Turn your existing WiFi into')
        ->and(homeKey('hero')->contents()->where('content_key', 'audiences.2.label')->value('value'))->toBe('Events, weddings & venues')
        ->and(homeKey('faq')->is_active)->toBeTrue()
        ->and(LandingPage::firstWhere('slug', 'home')->sections()->orderBy('sort_order')->pluck('section_key')->all())
        ->toBe(array_keys(PageBlueprint::definitions()['home']));

    // Running it again changes nothing.
    $this->artisan('tenafi:homepage-copy --dry-run')->expectsOutputToContain('already matches')->assertSuccessful();

    $backup = basename(Storage::disk('local')->files('cms-backups')[0]);
    $this->artisan("tenafi:homepage-copy --restore={$backup}")->assertSuccessful();
    expect(homeKey('hero')->contents()->where('content_key', 'title')->value('value'))->toBe('Glen’s live hero title')
        ->and(homeKey('hero')->contents()->where('content_key', 'like', 'audiences.%')->count())->toBe(0)
        ->and(homeKey('faq')->is_active)->toBeFalse();
});

it('quotes plans from the live /hosts pricing, never a second copy', function () {
    $pricing = LandingSection::whereHas('page', fn ($q) => $q->where('slug', 'hosts'))->where('section_key', 'pricing')->firstOrFail();
    $pricing->contents()->where('content_key', 'plans.0.price')->update(['value' => 'KES 2,800']);
    $pricing->contents()->where('content_key', 'plans.0.price_kes')->update(['value' => '2800']);
    LandingPage::clearCache();

    $this->get('/')->assertInertia(fn ($page) => $page
        ->where('plans.0', ['id' => 'basic', 'label' => 'Basic', 'price' => 'KES 2,800', 'price_kes' => 2800])
        ->has('plans', 3)
        ->where('sections', fn ($s) => str_contains(json_encode(collect($s)->firstWhere('section_key', 'hero')), '{price}')));
});

it('keeps the rules on the new copy', function () {
    $home = PageBlueprint::definitions()['home'];
    $copy = json_encode($home, JSON_UNESCAPED_UNICODE);

    expect($home['seo']['content']['meta_description'])->toContain('events and weddings')
        ->and($copy)->toContain('Every guest is asked, and no review is ever rewarded or filtered.')
        ->and($home['credibility']['content']['sources'])->toContain('Figures from Stay Awhile Rentals')
        ->and($home['comparison__problem']['content']['sources'])->toContain('Airbtics')
        ->and($home['detailed_features__home']['content']['sections'][3]['note'])->toBe('Example prices. You set your own.')
        // Plan prices are never typed into the homepage copy.
        ->and($copy)->not->toContain('KES 3,000')->not->toContain('KES 4,500');
});

it('keeps the homepage’s audience links and price line off /hosts and /business', function (string $slug) {
    // Their new look comes from tenafi:audience-pages (AudiencePagesTest), not the homepage copy.
    expect(PageBlueprint::definitions()[$slug]['hero']['content'])->not->toHaveKey('audiences')->not->toHaveKey('price_line');
})->with(['hosts', 'business']);
