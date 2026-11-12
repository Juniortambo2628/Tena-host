<?php

use App\Console\Commands\PricingOptionsCommand;
use App\Models\LandingContent;
use App\Models\LandingPage;
use App\Models\LandingSection;
use App\Models\Registration;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

/** Runs a snippet against resources/js/lib/pricingMath.js under Node and returns its JSON output. */
function pricingMath(string $script): mixed
{
    $lib = base_path('resources/js/lib/pricingMath.js');
    $process = new Process(['node', '--input-type=module', '-e', "import * as m from 'file://{$lib}'; console.log(JSON.stringify(({$script})(m)));"]);
    $process->mustRun();

    return json_decode($process->getOutput(), true);
}

function pricingField(string $slug, string $section, string $key): ?string
{
    return LandingContent::whereHas('section', fn ($q) => $q->where('section_key', $section)->whereHas('page', fn ($p) => $p->where('slug', $slug)))
        ->where('content_key', $key)->value('value');
}

const DEVICES_JS = '{lite: {price_kes: 25000, monthly_kes: 3000, months: 12}, pro: {price_kes: 42000, monthly_kes: 5000, months: 12}, outdoor: {price_kes: 48000, monthly_kes: 5500, months: 12}}';

it('works out the monthly price of every plan with every device', function () {
    $table = pricingMath('(m) => { const d = '.DEVICES_JS.'; return Object.fromEntries(Object.entries(d).map(([id, dev]) => [id, [3000, 4500, 6000].map((p) => m.planMonthly(p, "monthly", dev))])); }');

    expect($table)->toBe([
        'lite' => [6000, 7500, 9000],
        'pro' => [8000, 9500, 11000],
        'outdoor' => [8500, 10000, 11500],
    ]);
    expect(pricingMath('(m) => m.planMonthly(4500, "buy", {monthly_kes: 5000})'))->toBe(4500);
});

it('works out both worked examples', function () {
    expect(pricingMath('(m) => { const d = '.DEVICES_JS.'; return [m.yearOne(4500, d.pro), m.yearOne(4500, d.lite)]; }'))->toBe([
        ['buy' => 96000, 'monthly' => 114000, 'saving' => 18000],
        ['buy' => 79000, 'monthly' => 90000, 'saving' => 11000],
    ]);
});

it('reads the choice from the URL and passes it on to sign-up', function () {
    expect(pricingMath('(m) => [m.readChoice("?pay=monthly&device=pro", ["lite","pro"]), m.readChoice("?pay=x&device=gold", ["lite","pro"]), m.planHref("/business#b-signup", "growth", "monthly", "pro"), m.planHref("/business#b-signup", "growth", "buy", "pro")]'))->toBe([
        ['pay' => 'monthly', 'device' => 'pro'],
        ['pay' => 'buy', 'device' => 'lite'],
        '/business#b-signup?plan=growth&pay=monthly&device=pro',
        '/business#b-signup?plan=growth&pay=buy',
    ]);
});

it('splits a list field saved in any format into its lines', function (string $saved) {
    $lines = ['Everything in Starter', 'Campaigns', 'Reviews'];
    expect(pricingMath('(m) => m.splitLines('.json_encode($saved).').map((l) => l.replace(/<[^>]+>/g, "").trim()).filter(Boolean)'))->toBe($lines)
        ->and(PricingOptionsCommand::lines($saved))->toBe($lines);
})->with([
    'newlines' => "Everything in Starter\nCampaigns\nReviews",
    'windows' => "Everything in Starter\r\nCampaigns\r\nReviews",
    'br' => 'Everything in Starter<br>Campaigns<br/>Reviews',
    'paragraphs' => '<p>Everything in Starter</p><p>Campaigns</p><p>Reviews</p>',
    'escaped br' => 'Everything in Starter&lt;br&gt;Campaigns&lt;br /&gt;Reviews',
]);

it('never names the hardware maker or model on the public pages', function (string $url) {
    $response = $this->get($url)->assertOk();
    // What a visitor can see: the page's own props (not the route list) and its HTML without scripts.
    $props = collect($response->viewData('page')['props'])->except(['ziggy'])->all();
    $html = preg_replace('/<script\b.*?<\/script>|<link\b[^>]*>/is', '', $response->getContent());

    expect(json_encode($props, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))->not->toMatch('/ubiquiti|unifi|\bU7\b|mikrotik/i')
        ->and($html)->not->toMatch('/ubiquiti|unifi|\bU7\b|mikrotik/i');
})->with(['/', '/hosts', '/business']);

it('gives the hosts and business pricing the two ways to pay and the device FAQs', function (string $slug, string $plan, string $device) {
    expect(pricingField($slug, 'pricing', 'pay_buy_label'))->toBe('Buy the device')
        ->and(pricingField($slug, 'pricing', 'pay_monthly_badge'))->toBe('KES 0 upfront')
        ->and(pricingField($slug, 'pricing', 'example_plan'))->toBe($plan)
        ->and(pricingField($slug, 'pricing', 'example_device'))->toBe($device);

    $questions = LandingContent::whereHas('section', fn ($q) => $q->where('section_key', 'faq')->whereHas('page', fn ($p) => $p->where('slug', $slug)))
        ->where('content_key', 'like', 'items.%.question')->pluck('value');
    expect($questions)->toContain('Do I have to buy the device?', 'Which device do I need?', 'What if I leave?', 'Do I need more than one device?');
})->with([['hosts', 'starter', 'lite'], ['business', 'starter', 'pro']]);

it('fills only the new, empty pricing fields on a live page, and can be undone', function () {
    Storage::fake('local');
    // The live page as it is today: no pay options, an admin's own plan name and price,
    // and Growth's features saved with <br>.
    $pricing = LandingContent::whereHas('section', fn ($q) => $q->where('section_key', 'pricing')->whereHas('page', fn ($p) => $p->where('slug', 'business')))->get();
    $sectionId = $pricing->first()->section_id;
    LandingContent::where('section_id', $sectionId)->where(fn ($q) => $q->where('content_key', 'like', 'pay_%')->orWhere('content_key', 'like', 'example_%')->orWhere('content_key', 'like', 'bigger_%')->orWhere('content_key', 'like', 'device%'))->delete();
    LandingContent::where('section_id', $sectionId)->where('content_key', 'plans.0.label')->update(['value' => 'Café Starter']);
    LandingContent::where('section_id', $sectionId)->where('content_key', 'plans.0.price_kes')->update(['value' => '3,100']);
    LandingContent::updateOrCreate(['section_id' => $sectionId, 'content_key' => 'pay_buy_text'], ['value' => 'Our own words.', 'type' => 'text']);
    LandingContent::where('section_id', $sectionId)->where('content_key', 'plans.1.features')->update(['value' => 'Everything in Starter<br>Campaigns']);
    $footnote = pricingField('business', 'pricing', 'footnote');
    LandingPage::clearCache();

    $this->artisan('tenafi:pricing-options --dry-run')
        ->expectsOutputToContain('[business/pricing] pay_buy_label')
        ->expectsOutputToContain('[business/pricing] plans.1.features')
        ->expectsOutputToContain('Admin checklist')
        ->assertSuccessful();
    expect(pricingField('business', 'pricing', 'pay_buy_label'))->toBeNull();

    $this->artisan('tenafi:pricing-options')->assertSuccessful();
    expect(pricingField('business', 'pricing', 'pay_buy_label'))->toBe('Buy the device')
        ->and(pricingField('business', 'pricing', 'example_device'))->toBe('pro')
        ->and(pricingField('business', 'pricing', 'pay_buy_text'))->toBe('Our own words.')
        ->and(pricingField('business', 'pricing', 'plans.0.label'))->toBe('Café Starter')
        ->and(pricingField('business', 'pricing', 'plans.0.price_kes'))->toBe('3,100')
        ->and(pricingField('business', 'pricing', 'footnote'))->toBe($footnote)
        ->and(pricingField('business', 'pricing', 'plans.1.features'))->toBe("Everything in Starter\nCampaigns");

    $this->artisan('tenafi:pricing-options --dry-run')->expectsOutputToContain('Nothing to fill')->assertSuccessful();

    $backup = basename(collect(Storage::disk('local')->files('cms-backups'))->first(fn ($f) => str_contains($f, 'pricing-options')));
    $this->artisan("tenafi:pricing-options --restore={$backup}")->assertSuccessful();
    expect(pricingField('business', 'pricing', 'pay_buy_label'))->toBeNull()
        ->and(pricingField('business', 'pricing', 'plans.1.features'))->toBe('Everything in Starter<br>Campaigns');
});

it('shows the Growth features one per line in the admin as a textarea', function () {
    $editor = file_get_contents(resource_path('js/Components/CMS/SectionEditor.jsx'));

    expect($editor)->toContain("'features'")->toContain('One item per line');
});

it('gives the homepage pricing the two ways to pay, without adding device FAQs to its FAQ', function () {
    Storage::fake('local');
    // The live homepage as it is today: its pricing (copied from /hosts) has no "two ways to pay" words.
    LandingContent::whereHas('section', fn ($q) => $q->where('section_key', 'pricing')->whereHas('page', fn ($p) => $p->where('slug', 'v2')))
        ->where(fn ($q) => $q->where('content_key', 'like', 'pay_%')->orWhere('content_key', 'like', 'example_%')->orWhere('content_key', 'like', 'bigger_%')->orWhere('content_key', 'like', 'device%'))->delete();
    LandingPage::clearCache();
    $faqBefore = LandingContent::whereHas('section', fn ($q) => $q->where('section_key', 'faq')->whereHas('page', fn ($p) => $p->where('slug', 'v2')))->count();
    expect(pricingField('v2', 'pricing', 'pay_buy_label'))->toBeNull();

    $this->artisan('tenafi:pricing-options --dry-run')->expectsOutputToContain('[v2/pricing] pay_buy_label')->assertSuccessful();
    $this->artisan('tenafi:pricing-options')->assertSuccessful();

    expect(pricingField('v2', 'pricing', 'pay_buy_label'))->toBe('Buy the device')
        ->and(pricingField('v2', 'pricing', 'example_device'))->toBe('lite')
        ->and(LandingContent::whereHas('section', fn ($q) => $q->where('section_key', 'faq')->whereHas('page', fn ($p) => $p->where('slug', 'v2')))->count())->toBe($faqBefore);
});

// ---- "Your first 3 months are free" ----

it('works out year one with 0, 1 and 3 free months', function () {
    expect(pricingMath('(m) => { const d = '.DEVICES_JS.'; return [0, 1, 3].map((f) => [m.yearOne(4500, d.pro, f), m.yearOne(4500, d.lite, f)]); }'))->toBe([
        // 0: as before the offer (device + 12 x plan, or 12 x (plan + add-on)).
        [['buy' => 96000, 'monthly' => 114000, 'saving' => 18000], ['buy' => 79000, 'monthly' => 90000, 'saving' => 11000]],
        [['buy' => 91500, 'monthly' => 109500, 'saving' => 18000], ['buy' => 74500, 'monthly' => 85500, 'saving' => 11000]],
        // The brief: a café on Starter with TenaFi Pro and 3 free months, 82,500 vs 100,500.
        [['buy' => 82500, 'monthly' => 100500, 'saving' => 18000], ['buy' => 65500, 'monthly' => 76500, 'saving' => 11000]],
    ]);
});

it('works out each month’s price on both ways to pay, for every device', function (int $free) {
    $schedule = pricingMath('(m) => { const d = '.DEVICES_JS.'; return Object.fromEntries(Object.entries(d).map(([id, dev]) => [id, ["buy", "monthly"].map((pay) => m.freeSchedule(4500, pay, dev, '.$free.'))])); }');

    foreach (['lite' => 3000, 'pro' => 5000, 'outdoor' => 5500] as $id => $addon) {
        // Buy the device: KES 0 while free, then the plan.
        expect($schedule[$id][0])->toBe(['free' => $free, 'now' => 0, 'then' => null, 'after' => 4500]);
        // Pay monthly: the add-on only while free, then plan + add-on, then the plan.
        expect($schedule[$id][1])->toBe(['free' => $free, 'now' => $addon, 'then' => 4500 + $addon, 'after' => 4500]);
    }
})->with([0, 1, 3]);

it('keeps the plan links as they were with no free months, and adds the device and free months with them', function () {
    expect(pricingMath('(m) => [m.planHref("/business#b-signup", "starter", "buy", "pro", 0), m.planHref("/business#b-signup", "starter", "monthly", "pro", 0), m.planHref("/business#b-signup", "starter", "buy", "pro", 3), m.planHref("/business#b-signup", "starter", "monthly", "lite", 1)]'))->toBe([
        '/business#b-signup?plan=starter&pay=buy',
        '/business#b-signup?plan=starter&pay=monthly&device=pro',
        '/business#b-signup?plan=starter&pay=buy&device=pro&free_months=3',
        '/business#b-signup?plan=starter&pay=monthly&device=lite&free_months=1',
    ]);
});

it('words one free month and several', function () {
    expect(pricingMath('(m) => [1, 3].flatMap((f) => [m.fillFree("Your first {free_months} months are free", f), m.fillFree("Start with {free_months} months free", f), m.fillFree("for your first {free_months} months, device only", f)])'))->toBe([
        'Your first month is free', 'Start with 1 month free', 'for your first month, device only',
        'Your first 3 months are free', 'Start with 3 months free', 'for your first 3 months, device only',
    ])->and(pricingMath('(m) => [m.clampFree(""), m.clampFree("0"), m.clampFree("3 months"), m.clampFree(40)]'))->toBe([0, 0, 3, 12]);
});

it('has no free months on a page whose pricing has none, so it looks as before', function () {
    LandingContent::whereHas('section', fn ($q) => $q->where('section_key', 'pricing')->whereHas('page', fn ($p) => $p->where('slug', 'hosts')))
        ->where('content_key', 'free_months')->delete();
    LandingPage::clearCache();

    expect(LandingPage::liveFreeMonths('hosts'))->toBe(0);
    $this->get('/')->assertInertia(fn ($page) => $page->where('freeMonths', 0));
});

it('ships the offer on a fresh install: 3 free months, the banner words and the free FAQs', function (string $slug) {
    expect(pricingField($slug, 'pricing', 'free_months'))->toBe('3')
        ->and(pricingField($slug, 'pricing', 'free_banner_title'))->toBe('Your first {free_months} months are free')
        ->and(pricingField($slug, 'pricing', 'guarantee_text'))->toBeIn([null, ''])
        ->and(LandingPage::liveFreeMonths($slug))->toBe(3);

    $questions = LandingContent::whereHas('section', fn ($q) => $q->where('section_key', 'faq')->whereHas('page', fn ($p) => $p->where('slug', $slug)))
        ->where('content_key', 'like', 'items.%.question')->pluck('value');
    expect($questions)->toContain('What’s free?', 'Can I see it before I pay?', 'What happens after 3 months?');
})->with(['hosts', 'business']);

it('fills the free months on a live page without them, leaves an admin’s 0 alone, and splits a Growth list pasted with tabs', function () {
    Storage::fake('local');
    $section = fn (string $slug, string $key) => LandingSection::where('section_key', $key)->whereHas('page', fn ($p) => $p->where('slug', $slug))->first();
    $without = fn (string $slug, string $key, string $like) => LandingContent::where('section_id', $section($slug, $key)->id)->where('content_key', 'like', $like)->delete();

    // Live today: no free_* words; business switched the offer off with 0; Growth pasted with tabs.
    $without('hosts', 'pricing', 'free_%');
    $without('business', 'pricing', 'free_%');
    LandingContent::create(['section_id' => $section('business', 'pricing')->id, 'content_key' => 'free_months', 'value' => '0', 'type' => 'text']);
    LandingContent::where('section_id', $section('hosts', 'pricing')->id)->where('content_key', 'plans.2.features')->update(['value' => "Everything in Starter\t2 to 4 campaigns a month\tVIP WiFi tiers"]);
    $without('v2', 'hero', 'free_price_line');
    $faq = $section('hosts', 'faq');
    LandingContent::where('section_id', $faq->id)->whereIn('value', ['What’s free?', 'Can I see it before I pay?', 'What happens after 3 months?'])->get()
        ->each(fn ($q) => LandingContent::where('section_id', $faq->id)->whereIn('content_key', [$q->content_key, str_replace('.question', '.answer', $q->content_key)])->delete());
    LandingPage::clearCache();

    $this->artisan('tenafi:pricing-options --dry-run')
        ->expectsOutputToContain('[hosts/pricing] free_months')
        ->expectsOutputToContain('[hosts/pricing] plans.2.features')
        ->expectsOutputToContain('[v2/hero] free_price_line')
        ->doesntExpectOutputToContain('[business/pricing] free_months')
        ->assertSuccessful();
    expect(pricingField('hosts', 'pricing', 'free_months'))->toBeNull();

    $this->artisan('tenafi:pricing-options')->assertSuccessful();

    expect(pricingField('hosts', 'pricing', 'free_months'))->toBe('3')
        ->and(pricingField('business', 'pricing', 'free_months'))->toBe('0')
        ->and(pricingField('hosts', 'pricing', 'plans.2.features'))->toBe("Everything in Starter\n2 to 4 campaigns a month\nVIP WiFi tiers")
        ->and(pricingField('v2', 'hero', 'free_price_line'))->toBe('{free_months} months free. Then from {price}/month.')
        ->and(LandingContent::where('section_id', $faq->id)->where('value', 'What’s free?')->exists())->toBeTrue();

    $this->artisan('tenafi:pricing-options --dry-run')->expectsOutputToContain('Nothing to fill')->assertSuccessful();
});

it('records "See your WiFi page free first" on the sign-up', function () {
    Mail::fake();

    $this->postJson(route('signups.store'), [
        'type' => 'business', 'firstName' => 'Otieno', 'phone' => '+254722000111', 'businessName' => 'Kahawa House', 'plan' => 'starter', 'consent' => true, 'freeDesign' => '1',
    ])->assertCreated();

    expect(Registration::first()->answers)->toMatchArray(['freeDesign' => '1']);
    $this->postJson(route('signups.store'), ['type' => 'business', 'firstName' => 'A', 'phone' => '+254722000112', 'businessName' => 'B', 'plan' => 'starter', 'consent' => true, 'freeDesign' => 'yes'])
        ->assertStatus(422)->assertJsonValidationErrors('freeDesign');
});
