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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

function hostSignup(array $overrides = []): array
{
    return array_merge([
        'type' => 'host',
        'first_name' => 'Wanjiru',
        'last_name' => 'Kamau',
        'email' => 'wanjiru@example.com',
        'phone' => '+254 712 345 678',
        'units' => '2-5',
        'property_type' => 'Apartment',
        'location' => 'Kilimani, Nairobi',
        'primary_platform' => 'Airbnb',
        'biggest_challenge' => 'Filling quiet nights',
        'plan_interest' => 'Starter',
        'consent' => true,
    ], $overrides);
}

function businessSignup(array $overrides = []): array
{
    return array_merge([
        'type' => 'business',
        'first_name' => 'Otieno',
        'last_name' => 'Odhiambo',
        'email' => 'otieno@example.com',
        'phone' => '0712345678',
        'business_name' => 'Java Corner',
        'business_type' => 'Café / restaurant',
        'location' => 'Westlands',
        'branches' => '1',
        'biggest_challenge' => 'More Google reviews',
        'consent' => true,
    ], $overrides);
}

/*
|--------------------------------------------------------------------------
| Pages & CMS wiring
|--------------------------------------------------------------------------
*/

it('renders each public page from its own CMS sections', function (string $url, string $slug, string $firstSection) {
    $this->get($url)
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Public/Page')
            ->where('page.slug', $slug)
            ->where('sections.0.section_key', 'seo')
            ->where('sections.1.section_key', $firstSection)
            ->has('site.header')
            ->has('site.footer')
            ->has('site.plans')
            ->has('site.feature_status')
            ->has('seo.title')
        );
})->with([
    'main' => ['/', 'home', 'hero'],
    'hosts' => ['/hosts', 'hosts', 'hero'],
    'business' => ['/business', 'business', 'hero'],
]);

it('gives every sign-up page a signup section with its own anchor and type', function () {
    foreach (['hosts' => ['join', 'host'], 'business' => ['signup', 'business']] as $slug => [$anchor, $type]) {
        $signup = collect(LandingPage::publicSections($slug))->firstWhere('section_key', 'signup');

        expect($signup['content']['anchor'])->toBe($anchor)
            ->and($signup['content']['signup_type'])->toBe($type)
            ->and(json_decode($signup['content']['steps.0.fields'], true))->not->toBeEmpty();
    }
});

it('quotes the pitch-deck plans in KES site-wide', function () {
    $plans = LandingPage::siteSections()['plans']['content'];

    expect([$plans['plans.0.price'], $plans['plans.1.price'], $plans['plans.2.price']])
        ->toBe(['KES 3,000', 'KES 4,500', 'KES 6,000']);
});

it('no longer makes the claims removed in the relaunch', function () {
    $copy = LandingContent::whereHas('section', fn ($q) => $q->where('is_active', true))->pluck('value')->implode(' ');

    expect($copy)->not->toContain('5-star')
        ->not->toContain('WiFi 6')
        ->not->toContain('20%');
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

    expect($live)->toEqualCanonicalizing(['guest_homepage', 'pms_sync']);
});

/*
|--------------------------------------------------------------------------
| Sign-ups (POST /api/signups)
|--------------------------------------------------------------------------
*/

it('stores a host sign-up with consent wording from the CMS and alerts the team', function () {
    Mail::fake();
    Setting::setValue('signup_alert_emails', 'glen@example.com, ops@example.com');
    $admin = User::factory()->admin()->create();

    $this->postJson(route('signups.store'), hostSignup())->assertCreated();

    $registration = Registration::firstWhere('email', 'wanjiru@example.com');
    expect($registration->type)->toBe('host')
        ->and($registration->source_page)->toBe('hosts')
        ->and($registration->property_count)->toBe(2)
        ->and($registration->answers)->toBe(['plan_interest' => 'Starter'])
        ->and($registration->consented_at)->not->toBeNull()
        ->and($registration->consent_text)->toContain('I agree that TenaFi may store');

    Mail::assertSent(SignupAlertMail::class, fn ($mail) => $mail->hasTo('glen@example.com') && $mail->hasTo('ops@example.com'));
    Mail::assertSent(WaitlistConfirmationMail::class, fn ($mail) => $mail->hasTo('wanjiru@example.com'));
    $this->assertDatabaseHas('app_notifications', ['user_id' => $admin->id, 'type' => 'signup_received']);
});

it('stores a business sign-up with extra answers as JSON', function () {
    Mail::fake();

    $this->postJson(route('signups.store'), businessSignup())->assertCreated();

    $registration = Registration::firstWhere('email', 'otieno@example.com');
    expect($registration->type)->toBe('business')
        ->and($registration->business_name)->toBe('Java Corner')
        ->and($registration->source_page)->toBe('business')
        ->and($registration->answers)->toMatchArray(['business_type' => 'Café / restaurant', 'branches' => '1']);
});

it('validates sign-ups against the CMS field definitions', function () {
    $this->postJson(route('signups.store'), ['type' => 'business'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['first_name', 'email', 'phone', 'business_name', 'business_type', 'location', 'branches', 'consent']);

    $this->postJson(route('signups.store'), hostSignup(['units' => 'loads', 'email' => 'nope']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['units', 'email']);

    expect(Registration::count())->toBe(0);
});

it('requires consent before saving', function () {
    $this->postJson(route('signups.store'), hostSignup(['consent' => false]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('consent');
});

it('follows CMS changes to the sign-up questions', function () {
    $signup = LandingSection::whereHas('page', fn ($q) => $q->where('slug', 'business'))->where('section_key', 'signup')->first();
    $signup->contents()->where('content_key', 'steps.2.fields')->update(['value' => json_encode([
        ['key' => 'favourite_drink', 'label' => 'Favourite drink', 'type' => 'select', 'required' => true, 'options' => ['Chai', 'Coffee']],
    ])]);

    $this->postJson(route('signups.store'), businessSignup())->assertJsonValidationErrors('favourite_drink');

    Mail::fake();
    $this->postJson(route('signups.store'), businessSignup(['favourite_drink' => 'Chai']))->assertCreated();
    expect(Registration::first()->answers['favourite_drink'])->toBe('Chai');
});

it('closes sign-ups for a type whose form is disabled', function () {
    LandingSection::whereHas('page', fn ($q) => $q->where('slug', 'business'))->where('section_key', 'signup')->update(['is_active' => false]);

    $this->postJson(route('signups.store'), businessSignup())->assertJsonValidationErrors('type');
});

it('updates a repeat sign-up instead of duplicating it, without re-alerting', function () {
    Mail::fake();

    $this->postJson(route('signups.store'), hostSignup())->assertCreated();
    $this->postJson(route('signups.store'), hostSignup(['units' => '6-20']))->assertOk();

    expect(Registration::count())->toBe(1)
        ->and(Registration::first()->units)->toBe('6-20');
    Mail::assertSent(SignupAlertMail::class, 1);
});

it('posts sign-ups to the alert webhook when configured', function () {
    Mail::fake();
    Http::fake();
    Setting::setValue('signup_alert_webhook_url', 'https://hooks.example.com/tenafi');

    $this->postJson(route('signups.store'), businessSignup())->assertCreated();

    Http::assertSent(fn ($request) => $request->url() === 'https://hooks.example.com/tenafi'
        && $request['event'] === 'signup.created'
        && $request['signup']['business_name'] === 'Java Corner');
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

    expect($html)->toContain('<meta property="og:title" content="TenaFi for business | Turn your existing WiFi into more Google reviews">')
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
