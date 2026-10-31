<?php

use App\Models\Analytics;
use App\Models\LandingPage;
use App\Models\LandingSection;
use App\Services\Cms\PageBlueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function whatsappButton(): LandingSection
{
    return LandingSection::where('section_key', 'whatsapp_button')->whereHas('page', fn ($q) => $q->where('slug', LandingPage::SITE))->firstOrFail();
}

it('has every calculator assumption editable, labelled as an estimate', function () {
    $content = PageBlueprint::definitions()['home']['worth_calculator']['content'];

    expect($content)->toHaveKeys([
        'hosts_avg_stay', 'hosts_guests_per_stay', 'hosts_join_rate', 'hosts_direct_share', 'hosts_ota_fee',
        'business_open_days', 'business_join_rate', 'business_review_rate', 'business_offer_use',
        'events_connect_rate', 'events_join_rate', 'mode',
    ])->and($content['estimate_label'])->toBe('Estimate only.')
        ->and($content['subtitle'])->toContain('Your results will differ');
});

it('adds the calculator hidden and the WhatsApp menu fields empty, keeping the live button', function () {
    $home = LandingPage::firstWhere('slug', 'home');
    LandingSection::where('page_id', $home->id)->where('section_key', 'worth_calculator')->delete();
    whatsappButton()->contents()->where('content_key', 'like', 'menu_%')->delete();
    whatsappButton()->contents()->where('content_key', 'message_home')->update(['value' => 'Glen’s live message']);

    (require database_path('migrations/2026_10_31_000003_homepage_calculator_and_whatsapp_menu.php'))->up();

    expect(LandingSection::where('page_id', $home->id)->where('section_key', 'worth_calculator')->value('is_active'))->toBeFalse()
        ->and(whatsappButton()->contents()->where('content_key', 'menu_home')->value('value'))->toBe('')
        ->and(whatsappButton()->contents()->where('content_key', 'message_home')->value('value'))->toBe('Glen’s live message');
});

it('fills only the WhatsApp menu when the homepage copy is applied', function () {
    whatsappButton()->contents()->where('content_key', 'menu_home')->update(['value' => '']);
    whatsappButton()->contents()->where('content_key', 'number')->update(['value' => '+254 700 000 000']);

    $this->artisan('tenafi:homepage-copy')->assertSuccessful();

    expect(whatsappButton()->contents()->where('content_key', 'menu_home')->value('value'))->toContain('I’m planning an event |')
        ->and(whatsappButton()->contents()->where('content_key', 'number')->value('value'))->toBe('+254 700 000 000');
});

it('records which WhatsApp option was chosen', function () {
    $this->postJson('/api/track', ['event' => 'whatsapp_click', 'page' => 'home', 'option' => 'i-m-planning-an-even'])->assertSuccessful();
    $this->postJson('/api/track', ['event' => 'whatsapp_click', 'page' => 'home', 'option' => 'Bad Option!'])->assertJsonValidationErrors('option');

    expect(Analytics::where('metric_name', 'public.whatsapp_click:home:i-m-planning-an-even')->value('metric_value'))->toEqual(1);
});
