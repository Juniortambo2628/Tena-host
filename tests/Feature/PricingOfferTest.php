<?php

use App\Console\Commands\PricingOfferCommand;
use App\Models\LandingContent;
use App\Models\LandingPage;
use App\Models\LandingSection;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Symfony\Component\Process\Process;

/*
 * TenaFi's offer (Nov 2026): get your TenaFi device, pay once; your first 3
 * months of Basic are free; then choose Free, Basic, Starter or Growth.
 * Pay monthly is gone.
 */

/** Runs a snippet against resources/js/lib/pricingMath.js under Node and returns its JSON output. */
function offerMath(string $script): mixed
{
    $lib = base_path('resources/js/lib/pricingMath.js');
    $process = new Process(['node', '--input-type=module', '-e', "import * as m from 'file://{$lib}'; console.log(JSON.stringify(({$script})(m)));"]);
    $process->mustRun();

    return json_decode($process->getOutput(), true);
}

function offerField(string $slug, string $section, string $key): ?string
{
    return LandingContent::whereHas('section', fn ($q) => $q->where('section_key', $section)->whereHas('page', fn ($p) => $p->where('slug', $slug)))
        ->where('content_key', $key)->value('value');
}

function offerSection(string $slug, string $key): ?LandingSection
{
    return LandingSection::where('section_key', $key)->whereHas('page', fn ($p) => $p->where('slug', $slug))->first();
}

// ---- The sums (lib/pricingMath.js) ----

it('works out each plan’s price for the free months: the difference from Basic', function (int $free, array $expected) {
    expect(offerMath('(m) => [3000, 4500, 6000].map((p) => m.introPrice(p, 3000, '.$free.'))'))->toBe($expected);
})->with([
    'no free months: full price' => [0, [3000, 4500, 6000]],
    '1 free month' => [1, [0, 1500, 3000]],
    '3 free months' => [3, [0, 1500, 3000]],
]);

it('lets an admin override a plan’s price for the free months', function () {
    expect(offerMath('(m) => [m.introPrice(4500, 3000, 3, "1000"), m.introPrice(4500, 3000, 3, ""), m.introPrice(4500, 3000, 0, "1000"), m.introPrice(0, 3000, 3)]'))
        ->toBe([1000, 1500, 4500, 0]);
});

it('works out year one: the device once, the free months, then the plan', function () {
    expect(offerMath('(m) => [3, 1, 0].map((f) => [m.yearOne(4500, m.introPrice(4500, 3000, f), 42000, f), m.yearOne(3000, m.introPrice(3000, 3000, f), 25000, f)])'))->toBe([
        // The brief: a café on Starter with TenaFi Pro = 87,000; a rental on Basic with TenaFi Lite = 52,000.
        [87000, 52000],
        [93000, 58000],
        [96000, 61000],
    ]);
});

it('shows Free, Basic, Starter and Growth with free months, and only the paid plans at full price without', function () {
    $rows = '[{id:"basic",label:"Basic",price_kes:"3000"},{id:"starter",label:"Starter",price_kes:"4500"},{id:"growth",label:"Growth",price_kes:"6000"},{id:"free",label:"Free",price_kes:"0"}]';

    expect(offerMath('(m) => [3, 1, 0].map((f) => m.visiblePlans('.$rows.', f).map((p) => p.id))'))->toBe([
        ['free', 'basic', 'starter', 'growth'],
        ['free', 'basic', 'starter', 'growth'],
        ['basic', 'starter', 'growth'],
    ])
        // free_months 0 and the admin turned Free on.
        ->and(offerMath('(m) => m.visiblePlans('.$rows.', 0, true).map((p) => p.id)'))->toBe(['free', 'basic', 'starter', 'growth'])
        // The hero's "from" price skips Free.
        ->and(offerMath('(m) => m.cheapestPaid('.$rows.').id'))->toBe('basic');
});

it('keeps the device in the links and ignores ?pay=monthly', function () {
    expect(offerMath('(m) => [m.readDevice("?pay=monthly&device=pro", ["lite","pro","outdoor"]), m.readDevice("?pay=monthly", ["lite","pro"]), m.planHref("/business#signup", "starter", "pro", 3), m.planHref("/hosts#join", "free", "lite", 3), m.planHref("/hosts#join", "basic", "lite", 0)]'))->toBe([
        'pro',
        'lite',
        '/business#signup?plan=starter&device=pro&free_months=3',
        '/hosts#join?plan=free&device=lite&free_months=3',
        '/hosts#join?plan=basic&device=lite',
    ]);
});

it('words one free month and several, and never shows "(about $0)"', function () {
    expect(offerMath('(m) => [m.fillFree("{free_months} months of Basic, free", 1), m.fillFree("{free_months} months of Basic, free", 3), m.fillFree("a month for your first {free_months} months", 1), m.aboutUsd(0, 130), m.aboutUsd(1500, 130)]'))
        ->toBe(['1 month of Basic, free', '3 months of Basic, free', 'a month for your first month', '', '(about $12)']);
});

it('splits a list field saved in any format into its lines', function (string $saved) {
    $lines = ['Everything in Starter', '2 to 4 campaigns a month', 'Birthday and anniversary offers'];
    expect(offerMath('(m) => m.splitLines('.json_encode($saved).').map((l) => l.replace(/<[^>]+>/g, "").trim()).filter(Boolean)'))->toBe($lines)
        ->and(PricingOfferCommand::lines($saved))->toBe($lines);
})->with([
    'newlines' => "Everything in Starter\n2 to 4 campaigns a month\nBirthday and anniversary offers",
    'windows' => "Everything in Starter\r\n2 to 4 campaigns a month\r\nBirthday and anniversary offers",
    'br' => 'Everything in Starter<br>2 to 4 campaigns a month<br/>Birthday and anniversary offers',
    'paragraphs' => '<p>Everything in Starter</p><p>2 to 4 campaigns a month</p><p>Birthday and anniversary offers</p>',
    'line separators' => "Everything in Starter\u{2028}2 to 4 campaigns a month\u{2028}Birthday and anniversary offers",
]);

// ---- The pages ----

/** The page as a visitor gets it: its HTML without scripts, and its props without the route list. */
function visibleOffer(TestResponse $response): string
{
    $props = collect($response->viewData('page')['props'])->except(['ziggy'])->all();

    return preg_replace('/<script\b(?![^>]*application\/ld\+json).*?<\/script>|<link\b[^>]*>/is', '', $response->getContent())
        .json_encode($props, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

it('never offers Pay monthly, upfront or a monthly device add-on', function (bool $v2, string $url) {
    Setting::setValue('pricing_page_enabled', '1');
    Setting::setValue('v2_live', $v2 ? '1' : '0');

    // Field names aren't text a visitor reads ("upfront_label" now holds "Pay once").
    $page = str_replace('upfront_label', '', visibleOffer($this->get($url)->assertOk()));

    expect($page)->not->toMatch('/pay monthly|paying monthly|upfront|nothing upfront|\+\s*KES\s*[\d,]+\s*\/\s*mo\b|\/mo\b|for 12 months, then/i');
})->with(['live design' => true, 'previous design' => false])->with(['/', '/pricing', '/hosts', '/business', '/events']);

it('never names the hardware maker or model: copy, alt text, meta or JSON-LD', function (bool $v2, string $url) {
    Setting::setValue('pricing_page_enabled', '1');
    Setting::setValue('v2_live', $v2 ? '1' : '0');

    expect(visibleOffer($this->get($url)->assertOk()))->not->toMatch('/ubiquiti|unifi|\bU7\b|mikrotik/i');
})->with(['live design' => true, 'previous design' => false])->with(['/', '/pricing', '/hosts', '/business', '/events']);

it('ships the offer on a fresh install: 3 free months of Basic, the steps, Free, and the FAQs', function (string $slug, string $who) {
    $pricing = collect(LandingPage::publicSections($slug))->firstWhere('section_key', 'pricing')['content'];

    expect($pricing['free_months'])->toBe('3')
        ->and($pricing['base_free_plan'])->toBe('basic')
        ->and($pricing['steps.0.title'])->toBe('Get your TenaFi device')
        ->and($pricing['steps.1.title'])->toBe('{free_months} months of Basic, free')
        ->and([$pricing['plans.3.id'], $pricing['plans.3.price_kes'], $pricing['plans.3.cta']])->toBe(['free', '0', 'Get your device'])
        ->and($pricing['plans.3.features'])->toContain("Your {$who} list, export it any time")
        ->and($pricing['plans.0.cta'])->toBe('Start 3 months free')
        // One feature per line: Growth renders as a list.
        ->and(count(PricingOfferCommand::lines($pricing['plans.2.features'])))->toBe(4)
        ->and($pricing['plans.2.features'])->toContain('Featured on Tena Direct [[tena_direct]], plus VIP WiFi tiers [[vip_wifi]]')
        ->and($pricing['vat_note'] ?? '')->toBe('')
        ->and(LandingPage::liveOffer($slug)['freeMonths'])->toBe(3);

    $questions = collect(LandingPage::publicSections($slug))->firstWhere('section_key', 'faq')['content'];
    expect(collect($questions)->filter(fn ($v, $k) => str_ends_with($k, '.question'))->values()->all())
        ->toContain('What’s free?', 'What happens after 3 months?', 'Do I keep the device?', 'What’s the difference between Free and Basic?', 'Can I start on Starter or Growth?', 'How do I pay for the device?')
        ->not->toContain('Do I have to buy the device?');
})->with([['hosts', 'guest'], ['business', 'customer']]);

it('gives the homepage the offer from /hosts: its free months and three steps', function () {
    $this->get('/')->assertInertia(fn ($page) => $page
        ->where('offer.freeMonths', 3)
        ->where('offer.steps.0.title', 'Get your TenaFi device')
        ->has('offer.steps', 3));

    // free_months 0 on /hosts: no offer, and the usual price line.
    LandingContent::where('section_id', offerSection('hosts', 'pricing')->id)->where('content_key', 'free_months')->update(['value' => '0']);
    LandingPage::clearCache();
    $this->get('/')->assertInertia(fn ($page) => $page->where('offer.freeMonths', 0));
});

// ---- The live-safe command ----

it('brings a live page to the offer without touching the admin’s own wording, and can be undone', function () {
    Storage::fake('local');
    $pricing = offerSection('business', 'pricing');
    $set = fn (LandingSection $s, string $key, ?string $value) => $value === null
        ? LandingContent::where('section_id', $s->id)->where('content_key', $key)->delete()
        : LandingContent::updateOrCreate(['section_id' => $s->id, 'content_key' => $key], ['value' => $value, 'type' => 'text']);

    // The live page as it is today: no offer fields, no Free plan, the old wording TenaFi
    // shipped, an admin's own Basic features, Growth pasted with tabs and Pay monthly FAQs.
    LandingContent::where('section_id', $pricing->id)->where(fn ($q) => $q->whereIn('content_key', ['free_months', 'base_free_plan', 'show_free_plan', 'price_unit', 'offer_note', 'intro_line', 'then_line', 'device_line_card'])
        ->orWhere('content_key', 'like', 'steps.%')->orWhere('content_key', 'like', 'plans.3.%'))->delete();
    $set($pricing, 'subtitle', 'The device, installation, WiFi page, customer list and support. Month to month.');
    $set($pricing, 'example_text', '{label} pays {buy} in year one buying the device, or {monthly} paying monthly. Buying saves {saving}.');
    $set($pricing, 'pay_monthly_label', 'Pay monthly');
    $set($pricing, 'plans.0.features', "Glen’s own first line\nGlen’s own second line");
    $set($pricing, 'plans.0.cta', 'Choose Basic');
    $set($pricing, 'plans.1.features', "Everything in Basic\nA campaign every month on WhatsApp, SMS and email\nA monthly report on reviews and return visits [[monthly_report]]");
    $set($pricing, 'plans.2.features', "Everything in Starter\t2 to 4 campaigns a month\tVIP WiFi tiers");
    $faq = offerSection('business', 'faq');
    $faqKeys = LandingContent::where('section_id', $faq->id)->where('content_key', 'like', 'items.%')->pluck('content_key');
    $last = $faqKeys->map(fn ($k) => (int) explode('.', $k)[1])->max();
    LandingContent::where('section_id', $faq->id)->whereIn('value', ['What’s free?', 'Can I start on Starter or Growth?'])->get()
        ->each(fn ($q) => LandingContent::where('section_id', $faq->id)->whereIn('content_key', [$q->content_key, str_replace('.question', '.answer', $q->content_key)])->delete());
    $set($faq, 'items.'.($last + 1).'.question', 'Do I have to buy the device?');
    $set($faq, 'items.'.($last + 1).'.answer', 'No. Choose Pay monthly and pay KES 0 upfront. Buying the device upfront costs less over your first year.');
    $pricingFaq = offerSection('pricing', 'faq');
    LandingContent::where('section_id', $pricingFaq->id)->delete();
    $pricingFaq->delete();
    $footnote = offerField('business', 'pricing', 'footnote');
    LandingPage::clearCache();

    $this->artisan('tenafi:pricing-offer --dry-run')
        ->expectsOutputToContain('[business/pricing] free_months')
        ->expectsOutputToContain('[business/pricing] steps.0.title')
        ->expectsOutputToContain('[business/pricing] plans.3.id')
        ->expectsOutputToContain('[pricing] new section: faq')
        ->expectsOutputToContain('Admin checklist')
        ->assertSuccessful();
    expect(offerField('business', 'pricing', 'free_months'))->toBeNull();

    $this->artisan('tenafi:pricing-offer')->assertSuccessful();

    expect(offerField('business', 'pricing', 'free_months'))->toBe('3')
        ->and(offerField('business', 'pricing', 'steps.2.title'))->toBe('Then choose')
        ->and([offerField('business', 'pricing', 'plans.3.id'), offerField('business', 'pricing', 'plans.3.price_kes')])->toBe(['free', '0'])
        // Old wording TenaFi shipped: updated. The admin's own: untouched.
        ->and(offerField('business', 'pricing', 'subtitle'))->toContain('first 3 months of Basic are free')
        ->and(offerField('business', 'pricing', 'example_text'))->toBe('{label}: {device_price} once, {intro_phrase}, then {plan}. Year one: {year_one}.')
        ->and(offerField('business', 'pricing', 'plans.0.features'))->toBe("Glen’s own first line\nGlen’s own second line")
        ->and(offerField('business', 'pricing', 'plans.0.cta'))->toBe('Start 3 months free')
        ->and(offerField('business', 'pricing', 'plans.1.features'))->toContain('Birthday treats sent for you')
        ->and(offerField('business', 'pricing', 'plans.2.features'))->toBe("Everything in Starter\n2 to 4 campaigns a month\nVIP WiFi tiers")
        ->and(offerField('business', 'pricing', 'footnote'))->toBe($footnote)
        // Stored Pay monthly fields stay in the database (they are no longer shown).
        ->and(offerField('business', 'pricing', 'pay_monthly_label'))->toBe('Pay monthly')
        ->and(LandingContent::where('section_id', $faq->id)->where('value', 'Yes. You buy your TenaFi device once and it’s yours, and your first 3 months of Basic are free.')->exists())->toBeTrue()
        ->and(LandingContent::where('section_id', $faq->id)->where('value', 'What’s free?')->exists())->toBeTrue()
        ->and(offerSection('pricing', 'faq'))->not->toBeNull();

    // Running it again changes nothing.
    $this->artisan('tenafi:pricing-offer --dry-run')->expectsOutputToContain('Nothing to fill')->assertSuccessful();

    $backup = basename(collect(Storage::disk('local')->files('cms-backups'))->first(fn ($f) => str_contains($f, 'pricing-offer')));
    $this->artisan("tenafi:pricing-offer --restore={$backup}")->assertSuccessful();
    expect(offerField('business', 'pricing', 'free_months'))->toBeNull()
        ->and(offerField('business', 'pricing', 'plans.3.id'))->toBeNull()
        ->and(offerField('business', 'pricing', 'plans.2.features'))->toBe("Everything in Starter\t2 to 4 campaigns a month\tVIP WiFi tiers")
        ->and(offerSection('pricing', 'faq'))->toBeNull();
});

it('hides the retired Pay monthly fields from the admin forms, keeping them saved', function () {
    $editor = file_get_contents(resource_path('js/Components/CMS/SectionEditor.jsx'));

    expect($editor)->toContain('isRetiredField')->toContain('monthly_addon_kes|addon_months')->toContain('pay_(label|buy_');
});
