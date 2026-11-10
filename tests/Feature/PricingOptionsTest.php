<?php

use App\Console\Commands\PricingOptionsCommand;
use App\Models\LandingContent;
use App\Models\LandingPage;
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
