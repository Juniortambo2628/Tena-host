<?php

namespace Tests\Feature;

use App\Models\AccessPoint;
use App\Models\Property;
use App\Models\User;
use App\Services\Unifi\UnifiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class SplashContentTest extends TestCase
{
    use RefreshDatabase;

    private function property(array $branding = [], array $attributes = []): Property
    {
        $host = User::factory()->create(['role' => 'host']);
        $property = Property::factory()->create(['user_id' => $host->id, 'name' => 'Sunset Villa', 'address' => 'Watamu', 'branding_json' => $branding] + $attributes);
        AccessPoint::factory()->create(['property_id' => $property->id, 'mac_address' => '11:22:33:44:55:66']);

        return $property;
    }

    private function splash()
    {
        return $this->get('/portal?ap=11:22:33:44:55:66&id=aa:bb:cc:dd:ee:ff');
    }

    public function test_splash_shows_guest_content_in_tabs(): void
    {
        $property = $this->property(['template' => 'bento',
            'welcome_note' => "Karibu!\nThe coffee is on the counter.", 'host_name' => 'Amina',
            'house_rules' => ['Quiet hours 10pm – 7am'],
            'emergency' => [['label' => 'Police', 'phone' => '999']],
            'nearby' => [['name' => 'Papa Remo Beach', 'category' => 'nature', 'distance' => '5 min walk']],
            'offers' => [['title' => '20% off your next stay', 'when' => 'Until Dec']],
        ], ['review_url' => 'https://g.page/r/sunset/review']);
        $sister = Property::factory()->create(['user_id' => $property->user_id, 'name' => 'Coral Cottage', 'splash_image_path' => '/storage/properties/coral.jpg']);
        $stranger = Property::factory()->create(['name' => 'Not Yours Lodge']);
        $property->update(['branding_json' => $property->branding_json + ['stays' => [
            ['property_id' => $sister->id, 'price' => 'From KES 9,500'],
            ['property_id' => $stranger->id],
        ]]]);

        $this->splash()->assertOk()
            ->assertSee('data-tab="welcome"', false)->assertSee('data-tab="house"', false)->assertSee('data-tab="nearby"', false)
            ->assertSee('Karibu!<br />', false)->assertSee('Amina')
            ->assertSee('Quiet hours 10pm – 7am')
            ->assertSee('href="tel:999"', false)
            ->assertSee('Papa Remo Beach')->assertSee('google.com/maps/search', false)
            ->assertSee('https://g.page/r/sunset/review')
            ->assertSee('20% off your next stay')
            ->assertSee('Coral Cottage')->assertSee('/storage/properties/coral.jpg')->assertSee('From KES 9,500')
            ->assertDontSee('Not Yours Lodge');
    }

    public function test_hidden_sections_and_empty_content_are_left_out(): void
    {
        $this->property(['template' => 'clover', 'house_rules' => ['No parties'], 'sections' => ['rules' => false]]);

        $this->splash()->assertOk()->assertSee('sp--solo')->assertDontSee('No parties')->assertDontSee('data-tab="house"', false);
    }

    public function test_guest_text_is_escaped(): void
    {
        $this->property(['template' => 'bento', 'welcome_note' => '<script>alert(1)</script>', 'house_rules' => ['<b>bold</b>']]);

        $this->splash()->assertOk()->assertDontSee('<script>alert(1)</script>', false)->assertDontSee('<b>bold</b>', false);
    }

    public function test_connect_shows_content_page_with_a_way_on_when_the_host_has_content(): void
    {
        $this->property(['template' => 'bento', 'house_rules' => ['Check-out by 10am']]);
        $this->mock(UnifiService::class, function ($mock) {
            $mock->shouldReceive('authorizeGuest')->once()->andReturnTrue();
            $mock->shouldReceive('normalizeMac')->andReturnUsing(fn ($m) => strtolower($m));
        });

        $this->post('/portal/connect', [
            'id' => 'aa:bb:cc:dd:ee:ff', 'ap' => '11:22:33:44:55:66', 'url' => 'http://neverssl.com',
            'first_name' => 'Wanjiru', 'phone' => '0712 345 678', 'consent' => '1',
        ])->assertOk()->assertSee("You're connected", false)->assertSee('Check-out by 10am')->assertSee('href="http://neverssl.com"', false);
    }

    public function test_studio_saves_the_whole_design_and_drops_blank_rows(): void
    {
        Storage::fake('public');
        $property = $this->property(['template' => 'bento', 'house_rules' => ['Old rule'], 'offers' => [['title' => 'Old offer']]]);

        $this->actingAs($property->host)->post(route('host.properties.splash.update', $property), [
            'branding_json' => [
                'template' => 'monogram', 'accent' => '#111111', 'welcome_note' => 'Hello',
                'sections' => ['offers' => '0', 'nearby' => '1'],
                'house_rules' => ['Quiet after 10pm', ''],
                'nearby' => [['name' => 'Gede Ruins', 'category' => 'sights'], ['name' => '', 'category' => 'food']],
            ],
            'review_url' => 'https://g.page/r/new',
            'splash_image' => UploadedFile::fake()->image('cover.jpg'),
        ])->assertSessionHasNoErrors()->assertRedirect();

        $branding = $property->fresh()->branding_json;
        $this->assertSame('monogram', $branding['template']);
        $this->assertSame(['Quiet after 10pm'], $branding['house_rules']);
        $this->assertSame([], $branding['offers']); // not sent, so cleared
        $this->assertCount(1, $branding['nearby']);
        $this->assertFalse($branding['sections']['offers']);
        $this->assertSame('https://g.page/r/new', $property->fresh()->review_url);
        Storage::disk('public')->assertExists(str_replace('/storage/', '', $property->fresh()->splash_image_path));

        // The property form only changes the look and keeps the studio's content.
        $this->actingAs($property->host)->patch(route('host.properties.update', $property), [
            'name' => 'Sunset Villa', 'branding_json' => ['template' => 'nocturne', 'accent' => '#FF0000'],
        ])->assertSessionHasNoErrors();
        $branding = $property->fresh()->branding_json;
        $this->assertSame('nocturne', $branding['template']);
        $this->assertSame(['Quiet after 10pm'], $branding['house_rules']);
        $this->assertSame('Hello', $branding['welcome_note']);
    }

    public function test_studio_validates_rows_and_keeps_review_link_when_requests_are_on(): void
    {
        $property = $this->property([], ['review_url' => 'https://g.page/r/x', 'review_requests_enabled' => true]);

        $this->actingAs($property->host)->post(route('host.properties.splash.update', $property), [
            'branding_json' => [
                'template' => 'bento',
                'emergency' => [['label' => 'Police', 'phone' => '999; DROP']],
                'nearby' => [['name' => 'X', 'category' => 'casino', 'url' => 'javascript:alert(1)']],
            ],
            'review_url' => '',
        ])->assertSessionHasErrors(['branding_json.emergency.0.phone', 'branding_json.nearby.0.category', 'branding_json.nearby.0.url', 'review_url']);
    }

    public function test_draft_preview_renders_without_saving_and_is_owner_only(): void
    {
        $property = $this->property(['template' => 'bento', 'heading' => 'Saved heading']);
        $draft = ['branding_json' => ['template' => 'nocturne', 'heading' => 'Draft heading', 'offers' => [['title' => 'Draft offer']]]];

        $this->actingAs($property->host)->post(route('host.properties.splash-preview', $property), $draft)
            ->assertOk()->assertSee('splash--nocturne')->assertSee('Draft heading')->assertSee('Draft offer')->assertSee('Preview only');
        $this->assertSame('Saved heading', $property->fresh()->branding_json['heading']);

        $this->actingAs($property->host)->post(route('host.properties.splash-preview', $property), $draft + ['page' => 'connected'])
            ->assertOk()->assertSee("You're connected", false);

        $this->actingAs(User::factory()->create(['role' => 'host']))
            ->post(route('host.properties.splash-preview', $property), $draft)->assertForbidden();
    }

    public function test_studio_page_lists_only_the_hosts_other_properties(): void
    {
        $property = $this->property(['template' => 'retreat']);
        Property::factory()->create(['user_id' => $property->user_id, 'name' => 'Coral Cottage']);
        Property::factory()->create(['name' => 'Someone Else']);

        $this->actingAs($property->host)->get(route('host.properties.splash', $property))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Host/Properties/Splash')
                ->where('property.branding_json.template', 'horizon')
                ->has('otherProperties', 1)
                ->where('otherProperties.0.name', 'Coral Cottage'));

        $this->actingAs(User::factory()->create(['role' => 'host']))->get(route('host.properties.splash', $property))->assertForbidden();
    }
}
