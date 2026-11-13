<?php

use App\Models\LandingContent;
use App\Models\LandingPage;
use App\Models\LandingSection;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

/*
 * The final pricing (Nov 2026): Free KES 0, Basic 1,500, Starter 3,000,
 * Growth 4,500, per unit / per location; devices once; TenaFi free for 3
 * months, and the device is yours. Dollars from one site-wide rate.
 */

function finalMath(string $script): mixed
{
    $lib = base_path('resources/js/lib/pricingMath.js');
    $process = new Process(['node', '--input-type=module', '-e', "import * as m from 'file://{$lib}'; console.log(JSON.stringify(({$script})(m)));"]);
    $process->mustRun();

    return json_decode($process->getOutput(), true);
}

function finalField(string $slug, string $section, string $key): ?string
{
    return LandingContent::whereHas('section', fn ($q) => $q->where('section_key', $section)->whereHas('page', fn ($p) => $p->where('slug', $slug)))
        ->where('content_key', $key)->value('value');
}

function finalSection(string $slug, string $key): LandingSection
{
    return LandingSection::where('section_key', $key)->whereHas('page', fn ($p) => $p->where('slug', $slug))->firstOrFail();
}

it('works out every "(about $X)" from one rate, 129 shillings to the dollar by default', function () {
    expect(finalMath('(m) => [1500, 3000, 4500, 25000, 0].map((k) => m.aboutUsd(k))'))
        ->toBe(['(about $12)', '(about $23)', '(about $35)', '(about $194)', ''])
        ->and(finalMath('(m) => [m.aboutUsd(3000, 150), m.aboutUsd(3000, 0), m.withoutTypedUsd("per unit, per month (about $23)")]'))
        ->toBe(['(about $20)', '(about $23)', 'per unit, per month']);
});

it('works out the final prices for the first 3 months and year one', function () {
    expect(finalMath('(m) => [1500, 3000, 4500].map((p) => m.introPrice(p, 1500, 3))'))->toBe([0, 1500, 3000])
        ->and(finalMath('(m) => [m.yearOne(1500, m.introPrice(1500, 1500, 3), 25000, 3), m.yearOne(3000, m.introPrice(3000, 1500, 3), 42000, 3)]'))
        // A rental on Basic with TenaFi Lite; a café on Starter with TenaFi Pro.
        ->toBe([38500, 73500]);
});

it('shares the rate with every page and lets an admin change it', function () {
    $this->get('/')->assertInertia(fn ($page) => $page->where('kesPerUsd', 129));

    $admin = User::factory()->admin()->create();
    $this->actingAs($admin)->post(route('admin.settings.update'), ['settings' => ['kes_per_usd' => '0']])->assertSessionHasErrors('settings.kes_per_usd');
    $this->actingAs($admin)->post(route('admin.settings.update'), ['settings' => ['kes_per_usd' => '130']])->assertSessionHasNoErrors();
    LandingPage::clearCache();

    $this->get('/')->assertInertia(fn ($page) => $page->where('kesPerUsd', 130));
});

it('ships the final plans: KES prices with no typed dollars, message allowances, Free shown and the headline', function (string $slug) {
    $c = collect(LandingPage::publicSections($slug))->firstWhere('section_key', 'pricing')['content'];

    expect([$c['plans.0.price_kes'], $c['plans.1.price_kes'], $c['plans.2.price_kes'], $c['plans.3.price_kes']])->toBe(['1500', '3000', '4500', '0'])
        ->and([$c['plans.0.price'], $c['plans.1.price'], $c['plans.2.price']])->toBe(['KES 1,500', 'KES 3,000', 'KES 4,500'])
        ->and(collect($c)->filter(fn ($v, $k) => str_ends_with($k, '.unit'))->implode(' '))->not->toContain('about $')
        ->and($c['plans.0.messages_note'])->toBe('Email campaigns included. 100 SMS review requests a month.')
        ->and($c['plans.1.messages_note'])->toBe('300 WhatsApp or SMS messages a month.')
        ->and($c['plans.2.messages_note'])->toBe('700 WhatsApp or SMS messages a month.')
        ->and($c['show_free_plan'])->toBe('1')
        ->and($c['offer_headline'])->toBe('TenaFi free for {free_months} months, and the device is yours.')
        ->and(LandingPage::liveOffer($slug)['headline'])->toBe('TenaFi free for {free_months} months, and the device is yours.');
})->with(['hosts', 'business']);

it('puts "TenaFi free for 3 months, and the device is yours" in front on the homepage and /pricing', function () {
    $home = collect(LandingPage::publicSections('home'))->firstWhere('section_key', 'hero')['content'];
    $pricing = collect(LandingPage::publicSections('pricing'))->firstWhere('section_key', 'seo')['content'];

    expect($home['free_price_line'])->toBe('TenaFi free for {free_months} months, and the device is yours. Then from {price}/month.')
        ->and($pricing['meta_title'])->toBe('TenaFi pricing: free for 3 months, and the device is yours');
});

it('changes live prices only with --apply-prices, shows them before and after, and can be undone', function () {
    Storage::fake('local');
    $pricing = finalSection('hosts', 'pricing');
    $set = fn (string $key, string $value) => LandingContent::updateOrCreate(['section_id' => $pricing->id, 'content_key' => $key], ['value' => $value, 'type' => 'text']);

    // Live today: the old prices with typed dollars, Free hidden, no allowances, the trial sentence.
    $set('plans.0.price_kes', '3000');
    $set('plans.0.price', 'KES 3,000');
    $set('plans.0.unit', 'per unit, per month (about $23)');
    $set('show_free_plan', '0');
    LandingContent::where('section_id', $pricing->id)->where('content_key', 'plans.0.messages_note')->delete();
    $set('subtitle', 'Your guests already log in to your WiFi. Free trial from 1st December until 4 January 2027, with first installations from Monday 16 November, in time for the December high season.');
    LandingPage::clearCache();

    $this->artisan('tenafi:pricing-offer --dry-run')
        ->expectsOutputToContain('Prices (changed only with --apply-prices)')
        ->expectsOutputToContain('[hosts/pricing] plans.0.price_kes: 3000 -> 1500')
        ->expectsOutputToContain('[hosts/pricing] plans.0.unit')
        ->assertSuccessful();

    // Without the flag: everything but the prices.
    $this->artisan('tenafi:pricing-offer')->assertSuccessful();
    expect(finalField('hosts', 'pricing', 'plans.0.price_kes'))->toBe('3000')
        ->and(finalField('hosts', 'pricing', 'plans.0.unit'))->toBe('per unit, per month')
        ->and(finalField('hosts', 'pricing', 'show_free_plan'))->toBe('1')
        ->and(finalField('hosts', 'pricing', 'plans.0.messages_note'))->toBe('Email campaigns included. 100 SMS review requests a month.')
        ->and(finalField('hosts', 'pricing', 'subtitle'))->toBe('Your guests already log in to your WiFi. Get your TenaFi device and your first 3 months of Basic are free. Installations from Monday 16 November, in time for the December high season.');

    // With it: the prices.
    $this->artisan('tenafi:pricing-offer --apply-prices')->assertSuccessful();
    expect(finalField('hosts', 'pricing', 'plans.0.price_kes'))->toBe('1500')
        ->and(finalField('hosts', 'pricing', 'plans.0.price'))->toBe('KES 1,500');
    $this->artisan('tenafi:pricing-offer --dry-run --apply-prices')->expectsOutputToContain('Nothing to fill')->assertSuccessful();

    // The backup of the --apply-prices run (the one holding the price changes).
    $backup = collect(Storage::disk('local')->files('cms-backups'))
        ->first(fn ($f) => str_contains($f, 'pricing-offer') && str_contains(Storage::disk('local')->get($f), 'plans.0.price_kes'));
    $this->artisan('tenafi:pricing-offer --restore='.basename($backup))->assertSuccessful();
    expect(finalField('hosts', 'pricing', 'plans.0.price_kes'))->toBe('3000')
        ->and(finalField('hosts', 'pricing', 'plans.0.price'))->toBe('KES 3,000');
});
