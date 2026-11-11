<?php

use App\Console\Commands\PricingDevicesCommand;
use App\Models\LandingContent;
use App\Models\LandingPage;
use App\Models\LandingSection;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

function pricingContent(string $slug, string $sectionKey, string $key): ?LandingContent
{
    return LandingContent::whereHas('section', fn ($q) => $q->where('section_key', $sectionKey)->whereHas('page', fn ($p) => $p->where('slug', $slug)))
        ->where('content_key', $key)->first();
}

const OLD_FOOTNOTE = 'Then month to month. Extra devices at the same site: KES 1,500 a month each. WhatsApp and SMS allowances included; heavy use billed at cost. The device stays TenaFi’s; we maintain and replace it.';

it('keeps /pricing hidden from visitors and the sitemap until it is switched on', function () {
    $this->get('/pricing')->assertNotFound();
    $this->get('/sitemap.xml')->assertDontSee(url('/pricing'), false);

    // Admins preview it, never indexed while it's off.
    $this->actingAs(User::factory()->admin()->create())
        ->get('/pricing')
        ->assertOk()
        ->assertSee('content="noindex, nofollow"', false)
        ->assertInertia(fn ($page) => $page
            ->component('Public/V2/Pricing')
            ->where('sections', fn ($s) => collect($s)->pluck('section_key')->all() === ['seo', 'devices', 'pay_options'])
            ->where('pricingPage', null));
});

it('shows /pricing to everyone once switched on, with the three devices and live plans', function () {
    Setting::setValue('pricing_page_enabled', '1');

    $this->get('/pricing')->assertOk()->assertInertia(fn ($page) => $page
        ->where('sections.1.content', fn ($c) => $c['items.0.name'] === 'TenaFi Lite' && $c['items.1.price_kes'] === '42000' && $c['items.2.monthly_kes'] === '5500')
        ->where('sections.1.media.device_1_image', '/images/devices/photos/tenafi-pro.webp')
        ->where('plans', fn ($plans) => collect($plans)->pluck('price_kes')->all() === [3000, 4500, 6000])
        ->where('pricingPage', '/pricing')
        // The plans as each audience page shows them, for the switch at the top.
        ->where('planSections.hosts.section_key', 'pricing')
        ->where('planSections.business.section_key', 'pricing')
        ->where('planSections.business.content', fn ($c) => $c['plans.1.label'] === 'Starter'));

    $this->get('/sitemap.xml')->assertSee(url('/pricing'), false);
    // Each page's plans link to it.
    $this->get('/hosts')->assertInertia(fn ($page) => $page->where('pricingPage', '/pricing'));
});

it('lets an admin switch the pricing page on and off in Settings', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.settings.update'), ['settings' => ['pricing_page_enabled' => '1']])
        ->assertSessionHasNoErrors();

    expect(Setting::getValue('pricing_page_enabled'))->toBe('1');
});

it('drops the old device terms from a footnote and keeps the rest of it', function () {
    expect(PricingDevicesCommand::newFootnote(OLD_FOOTNOTE))
        ->toBe('Then month to month. WhatsApp and SMS allowances included; heavy use billed at cost. Your TenaFi device is bought upfront or over your first 12 months, and then it’s yours.')
        ->and(PricingDevicesCommand::newFootnote('The device stays TenaFi&rsquo;s; we maintain and replace it.'))
        ->toBe('Your TenaFi device is bought upfront or over your first 12 months, and then it’s yours.')
        ->and(PricingDevicesCommand::newFootnote('Glen’s own words.'))->toBe('Glen’s own words.');
});

it('puts the devices and pricing live with a dry run, a backup and a restore', function () {
    Storage::fake('local');
    pricingContent('hosts', 'pricing', 'footnote')->update(['value' => OLD_FOOTNOTE]);
    pricingContent('business', 'pricing', 'footnote')->update(['value' => OLD_FOOTNOTE]);
    $link = LandingContent::whereHas('section', fn ($q) => $q->where('section_key', 'header')->whereHas('page', fn ($p) => $p->where('slug', LandingPage::SITE)))
        ->where('content_key', 'like', 'links.%.href')->where('value', '/#pricing')->firstOrFail();
    LandingPage::clearCache();

    $this->artisan('tenafi:pricing-devices --dry-run')
        ->expectsOutputToContain('[hosts/pricing] footnote')
        ->expectsOutputToContain('[site/header] '.$link->content_key)
        ->expectsOutputToContain('/pricing: switch on')
        ->expectsOutputToContain('Dry run: nothing was saved')
        ->assertSuccessful();
    expect(pricingContent('hosts', 'pricing', 'footnote')->value)->toBe(OLD_FOOTNOTE)
        ->and(Setting::getValue('pricing_page_enabled', '0'))->toBe('0');

    $this->artisan('tenafi:pricing-devices')->expectsOutputToContain('Backup saved')->assertSuccessful();

    expect(pricingContent('hosts', 'pricing', 'footnote')->value)->not->toContain('KES 1,500')->toContain('and then it’s yours')
        ->and(pricingContent('business', 'pricing', 'footnote')->value)->not->toContain('stays TenaFi')
        ->and($link->fresh()->value)->toBe('/pricing')
        ->and(Setting::getValue('pricing_page_enabled'))->toBe('1');
    $this->get('/pricing')->assertOk();

    $this->artisan('tenafi:pricing-devices --dry-run')->expectsOutputToContain('already live')->assertSuccessful();

    $backup = basename(Storage::disk('local')->files('cms-backups')[0]);
    $this->artisan("tenafi:pricing-devices --restore={$backup}")->assertSuccessful();
    expect(pricingContent('hosts', 'pricing', 'footnote')->value)->toBe(OLD_FOOTNOTE)
        ->and($link->fresh()->value)->toBe('/#pricing')
        ->and(Setting::getValue('pricing_page_enabled'))->toBe('0');
});

it('gives each device card a picture slot in the editor', function () {
    $devices = LandingSection::whereHas('page', fn ($q) => $q->where('slug', 'pricing'))->where('section_key', 'devices')->firstOrFail();
    $keys = $devices->media()->pluck('media_key')->all();

    // Each device's main picture and three for its own page, and the "How it's installed" steps that have one.
    foreach ([0, 1, 2] as $i) {
        expect($keys)->toContain("device_{$i}_image", "device_{$i}_gallery_1", "device_{$i}_gallery_2", "device_{$i}_gallery_3");
    }
    expect($keys)->toContain('install_0_image', 'install_2_image', 'install_3_image', 'install_4_image')
        ->and($keys)->toHaveCount(16);
});
