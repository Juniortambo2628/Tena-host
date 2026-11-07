<?php

namespace Tests\Feature;

use App\Models\AccessPoint;
use App\Models\Property;
use App\Models\Registration;
use App\Models\User;
use App\Services\SignupConversionService;
use App\Services\Unifi\UnifiService;
use App\Support\SplashDesign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * The 12 splash templates, business types and per-template defaults
 * (resources/data/splash-templates.json).
 */
class SplashTemplatesTest extends TestCase
{
    use RefreshDatabase;

    private function property(array $branding = [], array $attributes = [], string $accountType = 'host'): Property
    {
        $host = User::factory()->create(['role' => 'host', 'account_type' => $accountType]);
        $property = Property::factory()->create(['user_id' => $host->id, 'name' => 'Kahawa House', 'branding_json' => $branding] + $attributes);
        AccessPoint::factory()->create(['property_id' => $property->id, 'mac_address' => '11:22:33:44:55:66']);

        return $property;
    }

    private function splash()
    {
        return $this->get('/portal?ap=11:22:33:44:55:66&id=aa:bb:cc:dd:ee:ff');
    }

    public function test_catalogue_matches_the_template_list_and_every_business_type_recommends_one(): void
    {
        $catalog = SplashDesign::catalog();

        $this->assertSame(SplashDesign::TEMPLATES, array_column($catalog['templates'], 'id'));
        foreach ($catalog['businessTypes'] as $type) {
            $this->assertContains($type['template'], SplashDesign::TEMPLATES, $type['id']);
        }
        $this->assertSame('Bars, lounges & nightlife', SplashDesign::template('nocturne')['for']);
        $this->assertSame('food', SplashDesign::template('nocturne')['group']);
    }

    public function test_every_template_renders_on_phone_markup(): void
    {
        $property = $this->property(['template' => 'bento'], ['business_type' => 'cafe']);

        foreach (SplashDesign::TEMPLATES as $template) {
            $property->update(['branding_json' => ['template' => $template]]);
            $this->splash()->assertOk()->assertSee('splash--'.$template, false);
        }
    }

    public function test_business_template_shows_its_defaults_tiles_and_band(): void
    {
        $this->property(['template' => 'brew'], ['business_type' => 'cafe']);

        $this->splash()->assertOk()
            ->assertSee('sp--band', false)->assertSee('--band: linear-gradient(135deg,#7A5640,#4A3123)', false)
            ->assertSee('Karibu! Free WiFi.')
            ->assertSee('class="sp-tiles"', false)->assertSee('View menu')->assertSee('Birthday treat')
            ->assertSee('/places/', false)
            ->assertSee('name="birthday_day"', false)->assertDontSee('name="email"', false)
            ->assertSee('Send me offers, news and a birthday treat.');
    }

    public function test_clinic_pages_ask_for_first_name_and_phone_only(): void
    {
        // Even a saved design that asks for more is overridden for Calm.
        $property = $this->property(['template' => 'calm', 'fields' => ['email' => true, 'birthday' => true]], ['business_type' => 'clinic']);

        $this->splash()->assertOk()
            ->assertSee('name="first_name"', false)->assertSee('name="phone"', false)
            ->assertDontSee('name="email"', false)->assertDontSee('name="birthday_day"', false)
            ->assertSee('Free WiFi while you wait.');

        $this->mock(UnifiService::class, function ($mock) {
            $mock->shouldReceive('authorizeGuest')->once()->andReturnTrue();
            $mock->shouldReceive('normalizeMac')->andReturnUsing(fn ($m) => strtolower($m));
        });

        $this->post('/portal/connect', [
            'id' => 'aa:bb:cc:dd:ee:ff', 'ap' => '11:22:33:44:55:66', 'first_name' => 'Wanjiru', 'phone' => '0712 345 678', 'consent' => '1',
            'email' => 'wanjiru@example.com', 'birthday_day' => '4', 'birthday_month' => '7',
        ])->assertRedirect(route('venue.show', $property));

        $guest = $property->guests()->sole();
        $this->assertNull($guest->email);
        $this->assertNull($guest->birthday);
    }

    public function test_saved_designs_from_the_first_five_templates_render_as_before(): void
    {
        // A rental host's old Nocturne page: no fields, opt-in or tiles saved.
        $this->property(['template' => 'bold', 'accent' => '#5B6CFF', 'heading' => '']);

        $this->splash()->assertOk()
            ->assertSee('splash--nocturne', false)->assertDontSee('sp--band sp--', false)
            ->assertSee('Welcome to <span class="sp-hl">Kahawa House</span>', false)
            ->assertSee('name="email"', false)->assertDontSee('name="birthday_day"', false)
            ->assertSee('Send me offers and early access to direct deals.')
            ->assertDontSee('class="sp-tiles"', false);
    }

    public function test_creating_a_property_applies_the_template_defaults(): void
    {
        $host = User::factory()->create(['role' => 'host', 'account_type' => 'business']);

        $this->actingAs($host)->post(route('host.properties.store'), [
            'name' => 'Sharp Cuts', 'business_type' => 'barber', 'occupancy_threshold' => 20,
            'branding_json' => ['template' => 'fade', 'accent' => '#C8352B'],
        ])->assertSessionHasNoErrors();

        $property = $host->properties()->sole();
        $this->assertSame('barber', $property->business_type);
        $this->assertSame('Fresh cut. Free WiFi.', $property->branding_json['heading']);
        $this->assertSame(['email' => false, 'birthday' => true], $property->branding_json['fields']);
        $this->assertSame(['Prices', 'Book a cut', 'Loyalty'], array_column($property->branding_json['tiles'], 'label'));
        $this->assertFalse($property->branding_json['sections']['rules']);
        $this->assertFalse($property->branding_json['sections']['stays']);
    }

    public function test_business_type_must_be_one_of_the_catalogue(): void
    {
        $host = User::factory()->create(['role' => 'host']);

        $this->actingAs($host)->post(route('host.properties.store'), ['name' => 'X', 'business_type' => 'spaceport', 'occupancy_threshold' => 20])
            ->assertSessionHasErrors('business_type');
    }

    public function test_switching_template_keeps_a_custom_headline_and_swaps_an_untouched_one(): void
    {
        $property = $this->property(['template' => 'brew', 'heading' => 'Karibu! Free WiFi.', 'accent' => '#6B4A35'], ['business_type' => 'cafe'], 'business');
        $owner = $property->host;

        $this->actingAs($owner)->patch(route('host.properties.update', $property), [
            'name' => 'Kahawa House', 'business_type' => 'restaurant', 'occupancy_threshold' => 20,
            'branding_json' => ['template' => 'bistro', 'accent' => '#6B4A35'],
        ])->assertSessionHasNoErrors();
        $this->assertSame('Karibu! Your table’s ready.', $property->fresh()->branding_json['heading']);

        $property->update(['branding_json' => ['heading' => 'Our own words'] + $property->fresh()->branding_json]);
        $this->actingAs($owner)->patch(route('host.properties.update', $property), [
            'name' => 'Kahawa House', 'business_type' => 'restaurant', 'occupancy_threshold' => 20,
            'branding_json' => ['template' => 'brew', 'accent' => '#6B4A35'],
        ])->assertSessionHasNoErrors();
        $this->assertSame('Our own words', $property->fresh()->branding_json['heading']);
    }

    public function test_business_signup_conversion_picks_the_recommended_template(): void
    {
        Mail::fake();
        Http::fake();
        $signup = Registration::create([
            'type' => 'business', 'first_name' => 'Amina', 'phone' => '+254712345678', 'business_name' => 'Glow Studio',
            'property_type' => 'other', 'answers' => ['plan' => 'basic', 'businessType' => 'Salon, barber or spa'],
        ]);

        ['user' => $user] = app(SignupConversionService::class)->convert($signup, ['whatsapp']);

        $property = $user->properties()->sole();
        $this->assertSame('salon', $property->business_type);
        $this->assertSame('bloom', $property->branding_json['template']);
        $this->assertSame('#C77B86', $property->branding_json['accent']);
    }

    public function test_society_shows_its_perks_for_any_account_and_keeps_offers_for_after_sign_in(): void
    {
        // A short-term rental account: perks still show, unlike other templates' quick links.
        $this->property([
            'template' => 'society', 'heading' => 'The Sip Society',
            'offers' => [['title' => '10% off your next order', 'code' => 'SIPSOCIETY10', 'when' => 'Valid 8–30 November']],
        ]);

        $this->get('/portal?ap=11:22:33:44:55:66&id=aa:bb:cc:dd:ee:ff&ssid=Venue%20WiFi')->assertOk()
            ->assertSee('splash--society', false)
            ->assertSee('Welcome — you’re almost online')->assertDontSee('· Venue WiFi')
            ->assertSee('class="sp-tiles"', false)->assertSee('Exclusive launches')->assertSee('Member-only deals')
            ->assertDontSee('data-tab="offers"', false)
            ->assertDontSee('SIPSOCIETY10');
    }

    public function test_society_connected_page_shows_the_member_perk_and_code(): void
    {
        $property = $this->property();
        $draft = ['branding_json' => [
            'template' => 'society', 'heading' => 'The Sip Society',
            'offers' => [['title' => '10% off your next order', 'detail' => '', 'code' => 'SIPSOCIETY10', 'when' => 'Valid 8–30 November']],
        ]];

        $this->actingAs($property->host)->post(route('host.properties.splash-preview', $property), $draft + ['page' => 'connected'])
            ->assertOk()
            ->assertSee('Welcome to the Sip Society')
            ->assertSee('Your member perk')->assertSee('10% off your next order')
            ->assertSee('SIPSOCIETY10')->assertSee('Valid 8–30 November')
            ->assertSee('Screenshot this to save your code');
    }

    public function test_society_offer_code_is_saved_and_limited(): void
    {
        $property = $this->property(['template' => 'society']);
        $owner = $property->host;

        $this->actingAs($owner)->post(route('host.properties.splash.update', $property), ['branding_json' => [
            'template' => 'society', 'offers' => [['title' => 'Free mocktail', 'code' => str_repeat('A', 41)]],
        ]])->assertSessionHasErrors('branding_json.offers.0.code');

        $this->actingAs($owner)->post(route('host.properties.splash.update', $property), ['branding_json' => [
            'template' => 'society', 'offers' => [['title' => 'Free mocktail', 'code' => 'MOCK1']],
        ]])->assertSessionHasNoErrors();
        $this->assertSame('MOCK1', $property->fresh()->branding_json['offers'][0]['code']);
    }

    public function test_a_business_on_society_stays_on_the_themed_connected_page_after_sign_in(): void
    {
        $property = $this->property([
            'template' => 'society', 'heading' => 'The Sip Society',
            'offers' => [['title' => '10% off your next order', 'code' => 'SIPSOCIETY10']],
        ], ['business_type' => 'bar'], 'business');
        $this->mock(UnifiService::class, function ($mock) {
            $mock->shouldReceive('authorizeGuest')->once()->andReturnTrue();
            $mock->shouldReceive('normalizeMac')->andReturnUsing(fn ($m) => strtolower($m));
        });

        $this->post('/portal/connect', [
            'id' => 'aa:bb:cc:dd:ee:ff', 'ap' => '11:22:33:44:55:66', 'url' => 'http://neverssl.com',
            'first_name' => 'Wanjiru', 'phone' => '0712345678', 'consent' => '1', 'email' => 'w@example.com',
        ])->assertOk()
            ->assertSee('splash--society', false)
            ->assertSee('Welcome to the Sip Society')->assertSee('SIPSOCIETY10');
        $this->assertSame(1, $property->guests()->count());
    }

    public function test_powered_by_line_shows_the_logo(): void
    {
        $this->property(['template' => 'society']);

        $this->splash()->assertOk()
            ->assertSee('Powered by', false)
            ->assertSee('/brand/tenafi-logo-header.png', false)->assertSee('/brand/tenafi-logo-footer.png', false);
    }
}
