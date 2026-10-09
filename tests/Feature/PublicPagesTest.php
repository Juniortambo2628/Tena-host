<?php

use App\Mail\SignupAlertMail;
use App\Mail\WaitlistConfirmationMail;
use App\Models\Analytics;
use App\Models\LandingContent;
use App\Models\LandingPage;
use App\Models\LandingSection;
use App\Models\PolicyDocument;
use App\Models\Registration;
use App\Models\Setting;
use App\Models\User;
use App\Services\Cms\PageBlueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

function hostSignup(array $overrides = []): array
{
    return array_merge([
        'type' => 'host',
        'firstName' => 'Wanjiru',
        'lastName' => 'Kamau',
        'phone' => '0712 345 678',
        'email' => 'wanjiru@example.com',
        'units' => '2-4',
        'area' => 'Kilimani, Nairobi',
        'platforms' => ['Airbnb', 'Vrbo'],
        'superhost' => 'Yes',
        'isp' => 'Safaricom',
        'plan' => 'starter',
        'estimatedPriceKES' => 4500,
        'consent' => true,
        'consentText' => 'I agree...',
        'submittedAt' => '2026-10-07T08:00:00Z',
        'source' => '/hosts',
    ], $overrides);
}

function businessSignup(array $overrides = []): array
{
    return array_merge([
        'type' => 'business',
        'firstName' => 'Otieno',
        'phone' => '+254 722 000 111',
        'role' => 'Owner',
        'businessName' => 'Kahawa House',
        'businessType' => 'Café',
        'locations' => '1 location',
        'area' => 'Westlands',
        'googleProfile' => 'Not sure',
        'plan' => 'basic',
        'consent' => true,
    ], $overrides);
}

/*
|--------------------------------------------------------------------------
| Pages & CMS wiring
|--------------------------------------------------------------------------
*/

it('renders each public page from its own CMS sections', function (string $url, string $slug, array $keys) {
    $this->get($url)
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Public/Page')
            ->where('page.slug', $slug)
            ->where('sections', fn ($sections) => collect($sections)->pluck('section_key')->all() === $keys)
            ->has('site.header')
            ->has('site.footer')
            ->has('site.feature_status')
            ->has('seo.title')
        );
})->with([
    'main' => ['/', 'home', ['seo', 'hero', 'path_cards', 'partners', 'comparison__problem', 'how_it_works', 'cta_banner__founding']],
    'hosts' => ['/hosts', 'hosts', ['seo', 'hero', 'stats__problem', 'features__outcomes', 'how_it_works', 'stats__commission', 'comparison__party', 'detailed_features', 'features__protect', 'credibility', 'pricing', 'cta_banner__founding', 'faq', 'cta_banner__crosssell', 'signup']],
    'business' => ['/business', 'business', ['seo', 'hero', 'features__why', 'comparison__qr', 'features__industries', 'how_it_works', 'detailed_features', 'stats__reviews', 'pricing', 'cta_banner__founding', 'faq', 'cta_banner__crosssell', 'signup']],
]);

it('retires sections of the old homepage that the handoff dropped', function () {
    $hosts = LandingPage::firstWhere('slug', 'hosts');

    expect($hosts->sections()->where('is_active', false)->pluck('section_key')->all())
        ->toContain('partners');
});

it('shows the same header links on every page, with megamenus built from each page\'s sections', function (string $url) {
    $this->get($url)->assertInertia(fn ($page) => $page
        ->where('site.header.content', fn ($c) => [$c['links.0.href'], $c['links.1.href'], $c['links.2.href']] === ['/hosts', '/business', '/#how'])
        ->where('menus./hosts', fn ($items) => collect($items)->pluck('label')->all() === ['Occupancy', 'How it works', 'Product', 'Protect your property', 'Pricing', 'Apply'])
        ->where('menus./business', fn ($items) => collect($items)->pluck('href')->all() === ['/business#b-how', '/business#b-product', '/business#b-reviews', '/business#b-pricing', '/business#signup'])
    );
})->with(['/', '/hosts', '/business']);

it('clears every page cache on a CMS write, even when some are cold', function () {
    LandingPage::publicSections('hosts'); // only the second page is warm
    LandingSection::clearCache();

    expect(Cache::has(LandingPage::cacheKey('hosts')))->toBeFalse();
});

it('keeps megamenus in sync with CMS edits to sections', function () {
    $admin = User::factory()->admin()->create();
    $pricing = LandingSection::whereHas('page', fn ($q) => $q->where('slug', 'hosts'))->where('section_key', 'pricing')->first();
    LandingPage::navMenus(); // warm the cache

    $this->actingAs($admin)->put(route('admin.landing.content.update'), [
        'section_id' => $pricing->id,
        'items' => [['content_key' => 'menu_label', 'value' => 'Plans & prices']],
    ]);

    expect(collect(LandingPage::navMenus()['/hosts'])->pluck('label'))->toContain('Plans & prices')->not->toContain('Pricing');

    $this->actingAs($admin)->put(route('admin.landing.sections.update', $pricing), ['is_active' => false]);

    expect(collect(LandingPage::navMenus()['/hosts'])->pluck('label'))->not->toContain('Plans & prices');
});

it('retires the old per-page nav sections', function () {
    $hosts = LandingPage::firstWhere('slug', 'hosts');
    $hosts->sections()->create(['section_key' => 'nav', 'title' => 'Page navigation']);

    (require database_path('migrations/2026_10_07_000004_replace_page_nav_with_megamenus.php'))->up();

    expect($hosts->sections()->where('section_key', 'nav')->exists())->toBeFalse();
});

it('gives every sign-up page a signup section with its own anchor and type', function () {
    foreach (['hosts' => ['join', 'host'], 'business' => ['signup', 'business']] as $slug => [$anchor, $type]) {
        $signup = collect(LandingPage::publicSections($slug))->firstWhere('section_key', 'signup');

        expect($signup['content']['anchor'])->toBe($anchor)
            ->and($signup['content']['signup_type'])->toBe($type)
            ->and(collect(json_decode($signup['content']['steps.0.fields'], true))->pluck('key')->take(4)->all())
            ->toBe(['firstName', 'lastName', 'phone', 'email']);
    }
});

it('quotes the pitch-deck plans in KES on both audience pages', function () {
    foreach (['hosts' => 'per unit', 'business' => 'per location'] as $slug => $unit) {
        $plans = collect(LandingPage::publicSections($slug))->firstWhere('section_key', 'pricing')['content'];

        expect([$plans['plans.0.id'], $plans['plans.1.id'], $plans['plans.2.id']])->toBe(['basic', 'starter', 'growth'])
            ->and([$plans['plans.0.price_kes'], $plans['plans.1.price_kes'], $plans['plans.2.price_kes']])->toBe(['3000', '4500', '6000'])
            ->and($plans['plans.1.badge'])->toBe('Most popular')
            ->and($plans['plans.0.unit'])->toContain($unit);
    }
});

it('no longer makes the claims removed in the relaunch', function () {
    $copy = LandingContent::whereHas('section', fn ($q) => $q->where('is_active', true))->pluck('value')->implode(' ');

    expect($copy)->not->toContain('5-star')
        ->not->toContain('WiFi 6')
        ->not->toContain('save up to 20%')
        ->not->toContain('Save up to 20%');
});

it('does not route the shared site page or unknown slugs', function () {
    $this->get('/site')->assertNotFound();
    $this->get('/does-not-exist')->assertNotFound();
});

it('reflects CMS edits on the public page immediately', function () {
    $admin = User::factory()->admin()->create();
    $hero = LandingSection::whereHas('page', fn ($q) => $q->where('slug', 'business'))->where('section_key', 'hero')->first();

    LandingPage::publicSections('business'); // warm the cache

    $this->actingAs($admin)->put(route('admin.landing.content.update'), [
        'section_id' => $hero->id,
        'items' => [['content_key' => 'cta_primary', 'value' => 'Get more reviews']],
    ])->assertRedirect();

    $this->get('/business')->assertInertia(fn ($page) => $page->where('sections.1.content.cta_primary', 'Get more reviews'));
});

it('lets admins switch between pages in the CMS', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get(route('admin.landing.index', ['page' => 'business']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Landing/Index')
            ->where('currentPage', 'business')
            ->has('pages', 4)
            ->where('sections.0.section_key', 'seo')
        );
});

it('keeps blueprint re-syncs additive so CMS edits survive', function () {
    $hero = LandingSection::whereHas('page', fn ($q) => $q->where('slug', 'hosts'))->where('section_key', 'hero')->first();
    $hero->contents()->where('content_key', 'title')->update(['value' => 'Edited by admin']);

    PageBlueprint::sync();

    expect($hero->contents()->where('content_key', 'title')->value('value'))->toBe('Edited by admin');
});

it('shows "Coming soon" only for features that are not live', function () {
    $status = collect(LandingPage::siteSections()['feature_status']['content']);

    $live = $status->filter(fn ($v, $k) => str_ends_with($k, '.status') && $v === 'live')
        ->keys()->map(fn ($k) => $status[str_replace('.status', '.key', $k)])->values()->all();

    expect($live)->toEqualCanonicalizing(['guest_homepage', 'pms_sync', 'outage_alerts', 'occupancy_alerts', 'monthly_report', 'business_homepage', 'mpesa_extras']);
});

/*
|--------------------------------------------------------------------------
| Sign-ups (POST /api/signups)
|--------------------------------------------------------------------------
*/

it('stores a host sign-up in Glen\'s spec format and alerts the team', function () {
    Mail::fake();
    $admin = User::factory()->admin()->create();

    $this->postJson(route('signups.store'), hostSignup())->assertCreated();

    $registration = Registration::firstWhere('type', 'host');
    expect($registration->phone)->toBe('+254712345678')
        ->and($registration->first_name)->toBe('Wanjiru')
        ->and($registration->location)->toBe('Kilimani, Nairobi')
        ->and($registration->units)->toBe('2-4')
        ->and($registration->property_count)->toBe(2)
        ->and($registration->source_page)->toBe('hosts')
        ->and($registration->answers)->toMatchArray([
            'platforms' => ['Airbnb', 'Vrbo'],
            'superhost' => 'Yes',
            'isp' => 'Safaricom',
            'plan' => 'starter',
            'estimatedPriceKES' => 4500,
        ])
        ->and($registration->consented_at)->not->toBeNull()
        // Stored from the CMS, not the client's copy.
        ->and($registration->consent_text)->toBe('I agree that TenaFi can contact me on WhatsApp, SMS or email about my application, and I accept the privacy policy.');

    // Glen is the default recipient (SIGNUP-FIELDS.md: "alert glen@tena.host").
    Mail::assertSent(SignupAlertMail::class, fn ($mail) => $mail->hasTo('glen@tena.host'));
    Mail::assertSent(WaitlistConfirmationMail::class, fn ($mail) => $mail->hasTo('wanjiru@example.com'));
    $this->assertDatabaseHas('app_notifications', ['user_id' => $admin->id, 'type' => 'signup_received']);
});

it('sends alerts to the recipients set in Settings', function () {
    Mail::fake();
    Setting::setValue('signup_alert_emails', 'ops@example.com, sales@example.com');

    $this->postJson(route('signups.store'), hostSignup())->assertCreated();

    Mail::assertSent(SignupAlertMail::class, fn ($mail) => $mail->hasTo('ops@example.com') && $mail->hasTo('sales@example.com') && ! $mail->hasTo('glen@tena.host'));
});

it('accepts a business sign-up without an email address', function () {
    Mail::fake();

    $this->postJson(route('signups.store'), businessSignup())->assertCreated();

    $registration = Registration::firstWhere('type', 'business');
    expect($registration->email)->toBeNull()
        ->and($registration->phone)->toBe('+254722000111')
        ->and($registration->business_name)->toBe('Kahawa House')
        ->and($registration->answers)->toMatchArray(['role' => 'Owner', 'businessType' => 'Café', 'locations' => '1 location', 'googleProfile' => 'Not sure', 'plan' => 'basic']);

    Mail::assertSent(SignupAlertMail::class);
    Mail::assertNotSent(WaitlistConfirmationMail::class);
});

it('validates sign-ups against the CMS field definitions', function () {
    $this->postJson(route('signups.store'), ['type' => 'business'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['firstName', 'phone', 'businessName', 'plan', 'consent']);

    $this->postJson(route('signups.store'), hostSignup(['units' => 'loads', 'platforms' => ['MySpace'], 'email' => 'nope', 'plan' => 'platinum']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['units', 'platforms.0', 'email', 'plan']);

    $this->postJson(route('signups.store'), hostSignup(['phone' => '12']))
        ->assertJsonValidationErrors(['phone' => 'valid WhatsApp number']);

    expect(Registration::count())->toBe(0);
});

it('normalises Kenyan phone numbers to E.164', function (string $input) {
    Mail::fake();

    $this->postJson(route('signups.store'), hostSignup(['phone' => $input]))->assertCreated();

    expect(Registration::first()->phone)->toBe('+254712345678');
})->with(['0712345678', '712 345 678', '254712345678', '+254 712 345 678']);

it('requires consent before saving', function () {
    $this->postJson(route('signups.store'), hostSignup(['consent' => false]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['consent' => 'Tick the box']);
});

it('follows CMS changes to the sign-up questions', function () {
    $signup = LandingSection::whereHas('page', fn ($q) => $q->where('slug', 'business'))->where('section_key', 'signup')->first();
    $fields = json_decode($signup->contents()->where('content_key', 'steps.1.fields')->value('value'), true);
    $fields[] = ['key' => 'hasWifi', 'label' => 'Do you have WiFi on site today?', 'type' => 'singleSelect', 'required' => true, 'options' => ['Yes, for customers', 'Only for staff', 'No']];
    $signup->contents()->where('content_key', 'steps.1.fields')->update(['value' => json_encode($fields)]);

    $this->postJson(route('signups.store'), businessSignup())->assertJsonValidationErrors('hasWifi');

    Mail::fake();
    $this->postJson(route('signups.store'), businessSignup(['hasWifi' => 'Only for staff']))->assertCreated();
    expect(Registration::first()->answers['hasWifi'])->toBe('Only for staff');
});

it('closes sign-ups for a type whose form is disabled', function () {
    LandingSection::whereHas('page', fn ($q) => $q->where('slug', 'business'))->where('section_key', 'signup')->update(['is_active' => false]);

    $this->postJson(route('signups.store'), businessSignup())->assertJsonValidationErrors('type');
});

it('updates a repeat sign-up from the same number instead of duplicating it', function () {
    Mail::fake();

    $this->postJson(route('signups.store'), hostSignup())->assertCreated();
    $this->postJson(route('signups.store'), hostSignup(['phone' => '+254 712 345 678', 'units' => '10-49']))->assertOk();

    expect(Registration::count())->toBe(1)
        ->and(Registration::first()->units)->toBe('10-49');
    Mail::assertSent(SignupAlertMail::class, 1);
});

it('posts sign-ups to the alert webhook when configured', function () {
    Mail::fake();
    Http::fake();
    Setting::setValue('signup_alert_webhook_url', 'https://hooks.example.com/tenafi');

    $this->postJson(route('signups.store'), businessSignup())->assertCreated();

    Http::assertSent(fn ($request) => $request->url() === 'https://hooks.example.com/tenafi'
        && $request['event'] === 'signup.created'
        && $request['signup']['business_name'] === 'Kahawa House'
        && $request['signup']['phone'] === '+254722000111');
});

it('lets admins filter sign-ups by type', function () {
    $admin = User::factory()->admin()->create();
    Registration::factory()->create(['type' => 'host']);
    Registration::factory()->create(['type' => 'business']);

    $this->actingAs($admin)->get(route('admin.registrations.index', ['type' => 'business']))
        ->assertInertia(fn ($page) => $page
            ->where('filters.type', 'business')
            ->has('registrations.data', 1)
            ->where('registrations.data.0.type', 'business')
        );
});

/*
|--------------------------------------------------------------------------
| Tracking, redirects, SEO, policies
|--------------------------------------------------------------------------
*/

it('counts funnel events per page per day', function () {
    $this->postJson(route('track'), ['event' => 'join_click', 'page' => 'hosts'])->assertNoContent();
    $this->postJson(route('track'), ['event' => 'join_click', 'page' => 'hosts'])->assertNoContent();
    $this->postJson(route('track'), ['event' => 'signup_step_2', 'page' => 'business'])->assertNoContent();

    expect((float) Analytics::where('metric_name', 'public.join_click:hosts')->value('metric_value'))->toBe(2.0)
        ->and(Analytics::where('metric_name', 'public.signup_step_2:business')->exists())->toBeTrue();
});

it('rejects unknown tracking events', function () {
    $this->postJson(route('track'), ['event' => 'drop_table'])->assertUnprocessable();
});

it('permanently redirects old URLs', function () {
    $this->get('/home')->assertStatus(301)->assertRedirect('/hosts');
    $this->get('/hosts.html')->assertStatus(301)->assertRedirect('/hosts');
});

it('renders social tags server-side from the CMS', function () {
    $html = $this->get('/business')->getContent();

    expect($html)->toContain('<meta property="og:title" content="TenaFi for business owners: turn your WiFi into more Google reviews">')
        ->toContain('<meta property="og:image" content="'.url('/legacy/assets/Tena-logo-square.jpg').'">')
        ->toContain('<link rel="canonical" href="'.url('/business').'">');
});

it('serves the privacy policy and terms through the public layout', function () {
    PolicyDocument::updateOrCreate(['slug' => 'privacy-policy'], ['title' => 'Privacy Policy', 'content' => '<p>We care.</p>', 'is_published' => true]);
    PolicyDocument::updateOrCreate(['slug' => 'terms-of-service'], ['title' => 'Terms', 'content' => '<p>Be nice.</p>', 'is_published' => false]);

    $this->get('/privacy')->assertOk()->assertInertia(fn ($page) => $page
        ->component('Public/Policy')
        ->where('document.title', 'Privacy Policy')
        ->has('site.footer')
    );
    $this->get('/terms')->assertNotFound();
});

it('lists every public page in the sitemap', function () {
    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertSee(url('/hosts'), false)
        ->assertSee(url('/business'), false)
        ->assertSee(url('/privacy'), false);
});

it('sends the founding buttons to the top of the host and business pages', function () {

    $hrefs = \App\Models\LandingContent::query()
        ->whereHas('section', fn ($q) => $q->where('section_key', 'cta_banner__founding'))
        ->where('content_key', 'like', '%href')
        ->pluck('value')->all();

    expect($hrefs)->toContain('/hosts', '/business')->not->toContain('/business#signup');
});
