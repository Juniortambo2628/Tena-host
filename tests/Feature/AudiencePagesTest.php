<?php

use App\Models\LandingPage;
use App\Models\LandingSection;
use App\Services\Cms\PageBlueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function audienceKey(string $slug, string $key): ?LandingSection
{
    return LandingSection::whereHas('page', fn ($q) => $q->where('slug', $slug))->where('section_key', $key)->first();
}

function audienceField(string $slug, string $key, string $field): ?string
{
    return audienceKey($slug, $key)?->contents()->where('content_key', $field)->value('value');
}

/** /hosts and /business as they are live today: no new sections or fields, and some admin-edited copy. */
function oldLiveAudiencePages(): void
{
    foreach (['demo', 'worth_calculator', 'comparison_table'] as $key) {
        audienceKey('hosts', $key)?->delete();
        audienceKey('business', $key)?->delete();
    }
    audienceKey('business', 'credibility')?->delete();

    foreach (['hosts', 'business'] as $slug) {
        audienceKey($slug, 'hero')->contents()->where(fn ($q) => $q->whereIn('content_key', ['visual', 'visual_screen', 'ticks', 'show_slots'])
            ->orWhere('content_key', 'like', 'visual_cards.%')->orWhere('content_key', 'like', 'callback_%'))->delete();
        audienceKey($slug, 'detailed_features')->contents()->where(fn ($q) => $q->where('content_key', 'like', 'sections.%.mockup')
            ->orWhere('content_key', 'like', 'sections.%.screen'))->delete();
        audienceKey($slug, 'hero')->contents()->where('content_key', 'title')->update(['value' => "Glen’s live {$slug} title"]);
    }
    audienceKey('hosts', 'credibility')->contents()->whereIn('content_key', ['style', 'quote', 'quote_name', 'sources'])->delete();
    LandingPage::clearCache();
}

it('adds the new sections to /hosts and /business hidden', function () {
    oldLiveAudiencePages();

    (require database_path('migrations/2026_11_02_000001_audience_pages_new_look.php'))->up();

    foreach (['hosts' => ['demo', 'worth_calculator', 'comparison_table'], 'business' => ['demo', 'worth_calculator', 'comparison_table', 'credibility']] as $slug => $keys) {
        foreach ($keys as $key) {
            expect(audienceKey($slug, $key))->not->toBeNull()->and(audienceKey($slug, $key)->is_active)->toBeFalse();
        }
    }
    $this->get('/hosts')->assertOk()->assertInertia(fn ($page) => $page->where('sections', fn ($s) => ! collect($s)->contains('section_key', 'demo')));
    $this->get('/business')->assertOk()->assertInertia(fn ($page) => $page->where('sections', fn ($s) => ! collect($s)->contains('section_key', 'credibility')));
});

it('applies the new look without touching the pages’ wording, and can restore', function () {
    Storage::fake('local');
    oldLiveAudiencePages();
    (require database_path('migrations/2026_11_02_000001_audience_pages_new_look.php'))->up();

    $this->artisan('tenafi:audience-pages --dry-run')
        ->expectsOutputToContain('[hosts/hero] visual')
        ->expectsOutputToContain('[business/detailed_features] sections.1.mockup')
        ->expectsOutputToContain('business/credibility: switch on')
        ->expectsOutputToContain('Dry run: nothing was saved')
        ->assertSuccessful();
    expect(audienceField('hosts', 'hero', 'visual'))->toBeNull()
        ->and(Storage::disk('local')->files('cms-backups'))->toBeEmpty();

    $this->artisan('tenafi:audience-pages')->expectsOutputToContain('Backup saved')->assertSuccessful();

    expect(audienceField('hosts', 'hero', 'title'))->toBe('Glen’s live hosts title')
        ->and(audienceField('business', 'hero', 'title'))->toBe('Glen’s live business title')
        ->and(audienceField('hosts', 'hero', 'visual'))->toBe('splash')
        ->and(audienceField('hosts', 'hero', 'callback_options'))->toBe('hosts|I run rentals')
        ->and(audienceField('business', 'hero', 'callback_options'))->toBe('business|I own a business')
        ->and(audienceField('business', 'detailed_features', 'sections.1.mockup'))->toBe('chat')
        ->and(audienceField('hosts', 'credibility', 'style'))->toBe('dark')
        ->and(audienceKey('business', 'credibility')->is_active)->toBeTrue()
        ->and(audienceKey('hosts', 'worth_calculator')->is_active)->toBeTrue();
    foreach (['hosts', 'business'] as $slug) {
        // Blueprint order; retired hidden sections (like the old partners strip) stay at the end.
        $live = LandingPage::firstWhere('slug', $slug)->sections()->orderBy('sort_order')->pluck('section_key')->all();
        expect(array_slice($live, 0, count(PageBlueprint::definitions()[$slug])))->toBe(array_keys(PageBlueprint::definitions()[$slug]));
    }
    // The homepage and /events are not this command's business.
    expect(collect(Storage::disk('local')->files('cms-backups'))->first())->toContain('audience-pages-');

    $this->get('/business')->assertOk()->assertInertia(fn ($page) => $page
        ->where('sections', fn ($s) => collect($s)->contains('section_key', 'worth_calculator')));

    $this->artisan('tenafi:audience-pages --dry-run')->expectsOutputToContain('already have the new look')->assertSuccessful();

    $backup = basename(Storage::disk('local')->files('cms-backups')[0]);
    $this->artisan("tenafi:audience-pages --restore={$backup}")->assertSuccessful();
    expect(audienceField('hosts', 'hero', 'visual'))->toBeNull()
        ->and(audienceField('hosts', 'hero', 'title'))->toBe('Glen’s live hosts title')
        ->and(audienceKey('business', 'credibility')->is_active)->toBeFalse();
});

it('keeps reviews and offers in separate messages in the drawn mockups', function () {
    foreach (['hosts', 'business'] as $slug) {
        $screens = collect(PageBlueprint::flatten(PageBlueprint::definitions()[$slug]['detailed_features']['content']))
            ->filter(fn ($v, $k) => str_ends_with($k, '.screen'));
        foreach ($screens as $screen) {
            foreach (preg_grep('/^msg:/', explode("\n", $screen)) as $message) {
                expect(str_contains(strtolower($message), 'review') && preg_match('/code|% off|discount/i', $message))->toBeFalse();
            }
        }
    }
});
