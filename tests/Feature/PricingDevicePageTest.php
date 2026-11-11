<?php

use App\Models\LandingContent;
use App\Models\LandingPage;
use App\Models\Setting;
use App\Models\User;

function deviceField(string $key): ?LandingContent
{
    return LandingContent::whereHas('section', fn ($q) => $q->where('section_key', 'devices')->whereHas('page', fn ($p) => $p->where('slug', 'pricing')))
        ->where('content_key', $key)->first();
}

it('keeps each device page hidden with /pricing, and lets admins preview it', function () {
    $this->get('/pricing/pro')->assertNotFound();

    $this->actingAs(User::factory()->admin()->create())
        ->get('/pricing/pro')
        ->assertOk()
        ->assertSee('content="noindex, nofollow"', false)
        ->assertInertia(fn ($page) => $page->component('Public/V2/Device')->where('index', 1));
});

it('shows a device’s own page with its specifications once /pricing is on', function () {
    Setting::setValue('pricing_page_enabled', '1');

    $this->get('/pricing/outdoor')->assertOk()->assertInertia(fn ($page) => $page
        ->component('Public/V2/Device')
        ->where('index', 2)
        ->where('section.content.items.2.name', 'TenaFi Outdoor')
        ->where('section.content.items.2.specs', fn ($specs) => str_contains($specs, 'WiFi standard | WiFi 7'))
        ->where('pay.section_key', 'pay_options')
        ->where('seo.title', 'TenaFi Outdoor: specifications and pricing | TenaFi'));

    $this->get('/pricing/not-a-device')->assertNotFound();
});

it('adds the device page fields to a live /pricing without changing edited words', function () {
    deviceField('items.1.specs')->delete();
    deviceField('items.1.tagline')->update(['value' => 'Glen’s own words']);
    LandingPage::clearCache();

    (require database_path('migrations/2026_11_18_000001_pricing_device_pages.php'))->up();

    expect(deviceField('items.1.specs')->value)->toContain('2.4 GHz, 5 GHz and 6 GHz')
        ->and(deviceField('items.1.tagline')->value)->toBe('Glen’s own words');
});
