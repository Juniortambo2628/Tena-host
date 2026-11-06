<?php

use App\Models\LandingContent;
use App\Models\LandingMedia;
use App\Models\LandingPage;
use App\Models\LandingSection;
use App\Models\Setting;
use App\Models\User;
use App\Services\Cms\SignupFormSchema;
use App\Services\Cms\V2Pages;
use App\Services\MediaUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('keeps /v2 hidden from the public until Admin -> Settings opens it', function () {
    $this->get('/v2')->assertNotFound();
    $this->actingAs(User::factory()->host()->create())->get('/v2')->assertNotFound();

    Setting::setValue('v2_pages_enabled', '1', 'general', 'boolean');
    auth()->logout();

    $this->get('/v2')->assertOk()->assertInertia(fn ($page) => $page->component('Public/V2/Home'));
});

it('lets admins preview /v2 while it is switched off', function () {
    $this->actingAs(User::factory()->admin()->create())->get('/v2')->assertOk()
        ->assertInertia(fn ($page) => $page->component('Public/V2/Home'));
});

it('shows the live homepage words and the shared site-wide header and footer, never indexed', function () {
    Setting::setValue('v2_pages_enabled', '1', 'general', 'boolean');

    $this->get('/v2')
        ->assertSee('noindex, nofollow', false)
        ->assertInertia(fn ($page) => $page
            ->where('seo.index', false)
            ->where('sections', fn ($sections) => collect($sections)->pluck('section_key')->contains('hero')
                && collect($sections)->pluck('section_key')->contains('path_cards'))
            ->has('site.header')
            ->has('site.footer'));

    // The live homepage is unchanged and still indexed.
    $this->get('/')->assertInertia(fn ($page) => $page->component('Public/Page')->where('seo.index', true));
});

it('saves the switch from Admin -> Settings', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.settings.update'), ['settings' => ['v2_pages_enabled' => '1']])
        ->assertSessionHasNoErrors();

    expect(Setting::getValue('v2_pages_enabled'))->toBe('1');
});

function v2Section(string $key): LandingSection
{
    return LandingSection::whereHas('page', fn ($q) => $q->where('slug', V2Pages::HOME))->where('section_key', $key)->firstOrFail();
}

it('makes /v2 its own CMS page, copied from the live homepage, kept out of menus and the sitemap', function () {
    $page = LandingPage::where('slug', V2Pages::HOME)->sole();
    expect($page->is_routable)->toBeFalse();

    $keys = $page->sections()->orderBy('sort_order')->pluck('section_key')->all();
    expect($keys)->toBe([
        'seo', 'hero', 'partners', 'path_cards', 'comparison__problem', 'detailed_features__home',
        'showcase', 'how_it_works', 'comparison_table', 'credibility', 'pricing', 'faq', 'cta_banner__founding',
    ]);

    // Same words and pictures as the live sections they came from.
    $liveHero = LandingSection::whereHas('page', fn ($q) => $q->where('slug', 'home'))->where('section_key', 'hero')->with('contents')->sole();
    expect(v2Section('hero')->contents()->pluck('value', 'content_key')->all())
        ->toEqual($liveHero->contents->pluck('value', 'content_key')->all());
    expect(v2Section('showcase')->media()->count())->toBe(count(V2Pages::SHOWCASE));

    // Running it again changes nothing.
    V2Pages::createHome();
    expect(LandingPage::where('slug', V2Pages::HOME)->count())->toBe(1)
        ->and($page->sections()->count())->toBe(count($keys));

    expect(collect(LandingPage::navMenus())->keys())->not->toContain('/v2');
    $this->get('/sitemap.xml')->assertDontSee('/v2');
    $this->get('/v2-anything')->assertNotFound();
});

it('serves the v2 page’s own sections, and editing them leaves the live homepage alone', function () {
    Setting::setValue('v2_pages_enabled', '1', 'general', 'boolean');

    LandingContent::where('section_id', v2Section('hero')->id)->where('content_key', 'badge')->update(['value' => 'Only on v2']);
    LandingPage::clearCache();

    $this->get('/v2')->assertInertia(fn ($page) => $page
        ->where('sections', fn ($sections) => collect($sections)->firstWhere('section_key', 'hero')['content']['badge'] === 'Only on v2'
            && collect($sections)->pluck('section_key')->contains('showcase')
            && collect($sections)->pluck('section_key')->contains('pricing')));

    $this->get('/')->assertInertia(fn ($page) => $page
        ->where('sections', fn ($sections) => collect($sections)->firstWhere('section_key', 'hero')['content']['badge'] !== 'Only on v2'));
});

it('opens the v2 page from Admin -> Public Pages', function () {
    $this->actingAs(User::factory()->admin()->create())->get(route('admin.landing.index', ['page' => V2Pages::HOME]))
        ->assertInertia(fn ($page) => $page->where('currentPage', V2Pages::HOME)
            ->where('pages', fn ($pages) => collect($pages)->firstWhere('slug', V2Pages::HOME)['url'] === '/v2'));
});

it('keeps a picture file while another page still shows it', function () {
    Storage::fake('public');
    Storage::disk('public')->put('landing/shared.jpg', 'x');
    $row = fn (string $key) => LandingMedia::create([
        'section_id' => v2Section('hero')->id, 'media_key' => $key, 'original_path' => '/landing/shared.jpg', 'mime_type' => 'image/jpeg', 'file_size' => 1,
    ]);
    [$live, $copy] = [$row('test_a'), $row('test_b')];

    app(MediaUploadService::class)->delete($copy);
    Storage::disk('public')->assertExists('landing/shared.jpg');

    app(MediaUploadService::class)->delete($live);
    Storage::disk('public')->assertMissing('landing/shared.jpg');
});

it('makes /v2/hosts, /v2/business and /v2/events their own CMS pages, without copying the sign-up forms', function () {
    foreach (V2Pages::AUDIENCES as $audience => $keys) {
        $page = LandingPage::where('slug', V2Pages::audienceSlug($audience))->sole();
        $copied = $page->sections()->orderBy('sort_order')->pluck('section_key')->all();
        $live = LandingPage::where('slug', $audience)->sole()->sections()->orderBy('sort_order')->pluck('section_key')
            ->intersect($keys)->values()->all();

        expect($page->is_routable)->toBeFalse()
            ->and($copied)->toBe($live)
            ->and($copied)->not->toContain('signup');
    }

    // The server still validates each sign-up against the live page's form.
    expect(SignupFormSchema::forType('host')->pageSlug)->toBe('hosts')
        ->and(SignupFormSchema::forType('business')->pageSlug)->toBe('business')
        ->and(SignupFormSchema::forType('event')->pageSlug)->toBe('events');

    V2Pages::createAudiences();
    expect(LandingPage::where('slug', 'like', 'v2-%')->count())->toBe(3);
});

it('serves the audience pages behind the switch, with the live sign-up form and links to the others', function () {
    $this->get('/v2/hosts')->assertNotFound();
    Setting::setValue('v2_pages_enabled', '1', 'general', 'boolean');

    $this->get('/v2/hosts')->assertOk()->assertSee('noindex, nofollow', false)
        ->assertInertia(fn ($page) => $page->component('Public/V2/Audience')
            ->where('audience', 'hosts')
            ->where('seo.index', false)
            ->where('sections', fn ($sections) => collect($sections)->pluck('section_key')->contains('journey'))
            ->where('signup.page', 'hosts')
            ->where('signup.sections', fn ($sections) => collect($sections)->pluck('section_key')->sort()->values()->all() === ['pricing', 'signup'])
            ->has('others', 2)
            ->where('others.0.to', '/v2/business'));

    $this->get('/v2/events')->assertOk()->assertInertia(fn ($page) => $page->where('signup.page', 'events')
        ->where('signup.sections', fn ($sections) => collect($sections)->pluck('section_key')->all() === ['signup']));
    $this->get('/v2/somewhere')->assertNotFound();
});

it('opens each audience page from Admin -> Public Pages', function () {
    $this->actingAs(User::factory()->admin()->create())->get(route('admin.landing.index', ['page' => 'v2-events']))
        ->assertInertia(fn ($page) => $page->where('currentPage', 'v2-events')
            ->where('pages', fn ($pages) => collect($pages)->firstWhere('slug', 'v2-events')['url'] === '/v2/events'
                && collect($pages)->firstWhere('slug', 'events')['url'] === '/events'));
});
