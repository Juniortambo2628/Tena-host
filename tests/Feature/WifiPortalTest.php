<?php

namespace Tests\Feature;

use App\Models\AccessPoint;
use App\Models\Guest;
use App\Models\Property;
use App\Models\User;
use App\Services\Unifi\UnifiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class WifiPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_splash_page_renders_with_unifi_params(): void
    {
        $response = $this->get('/portal?'.http_build_query([
            'id' => 'aa:bb:cc:dd:ee:ff',
            'ap' => '11:22:33:44:55:66',
            'ssid' => 'Tena Guest',
            'url' => 'http://example.com',
        ]));

        $response->assertOk();
        $response->assertSee('Connect to WiFi');
        $response->assertSee('Tena Guest');
    }

    public function test_splash_shows_property_name_when_ap_is_known(): void
    {
        $host = User::factory()->create(['role' => 'host']);
        $property = Property::factory()->create(['user_id' => $host->id, 'name' => 'Sunset Villa']);
        AccessPoint::factory()->create([
            'property_id' => $property->id,
            'mac_address' => '11:22:33:44:55:66',
        ]);

        $response = $this->get('/portal?ap=11:22:33:44:55:66&id=aa:bb:cc:dd:ee:ff');

        $response->assertOk();
        $response->assertSee('Sunset Villa');
    }

    public function test_connect_authorizes_guest_and_redirects_to_original_url(): void
    {
        $this->mock(UnifiService::class, function ($mock) {
            $mock->shouldReceive('authorizeGuest')
                ->once()
                ->with('aa:bb:cc:dd:ee:ff', '11:22:33:44:55:66')
                ->andReturnTrue();
            $mock->shouldReceive('normalizeMac')->andReturnUsing(fn ($m) => strtolower($m));
        });

        $response = $this->post('/portal/connect', [
            'id' => 'aa:bb:cc:dd:ee:ff',
            'ap' => '11:22:33:44:55:66',
            'url' => 'http://neverssl.com',
        ]);

        $response->assertRedirect('http://neverssl.com');
    }

    public function test_connect_shows_connected_page_when_no_original_url(): void
    {
        $this->mock(UnifiService::class, function ($mock) {
            $mock->shouldReceive('authorizeGuest')->once()->andReturnTrue();
            $mock->shouldReceive('normalizeMac')->andReturnUsing(fn ($m) => strtolower($m));
        });

        $response = $this->post('/portal/connect', [
            'id' => 'aa:bb:cc:dd:ee:ff',
        ]);

        $response->assertOk();
        $response->assertSee("You're connected", false);
    }

    public function test_connect_returns_to_splash_with_error_on_failure(): void
    {
        $this->mock(UnifiService::class, function ($mock) {
            $mock->shouldReceive('authorizeGuest')->once()->andReturnFalse();
            $mock->shouldReceive('normalizeMac')->andReturnUsing(fn ($m) => strtolower($m));
        });

        $response = $this->post('/portal/connect', [
            'id' => 'aa:bb:cc:dd:ee:ff',
        ]);

        $response->assertRedirect();
        $this->assertStringContainsString('error=', $response->headers->get('Location'));
    }

    public function test_connect_requires_client_mac(): void
    {
        $response = $this->post('/portal/connect', []);
        $response->assertSessionHasErrors('id');
    }

    public function test_normalize_mac_formats_bare_hex(): void
    {
        $service = new UnifiService(baseUrl: 'https://x', username: 'u', password: 'p');
        $this->assertSame('aabbccddeeff', str_replace(':', '', $service->normalizeMac('AA-BB-CC-DD-EE-FF')));
        $this->assertSame('aa:bb:cc:dd:ee:ff', $service->normalizeMac('AABBCCDDEEFF'));
    }

    private function knownAp(): Property
    {
        $host = User::factory()->create(['role' => 'host']);
        $property = Property::factory()->create(['user_id' => $host->id, 'name' => 'Sunset Villa']);
        AccessPoint::factory()->create(['property_id' => $property->id, 'mac_address' => '11:22:33:44:55:66']);

        return $property;
    }

    private function mockUnifi(int $authorizations = 1): void
    {
        $this->mock(UnifiService::class, function ($mock) use ($authorizations) {
            $mock->shouldReceive('authorizeGuest')->times($authorizations)->andReturnTrue();
            $mock->shouldReceive('normalizeMac')->andReturnUsing(fn ($m) => strtolower($m));
        });
    }

    private function connectAsGuest(array $overrides = [])
    {
        return $this->post('/portal/connect', array_merge([
            'id' => 'aa:bb:cc:dd:ee:ff',
            'ap' => '11:22:33:44:55:66',
            'first_name' => 'Wanjiru',
            'phone' => '0712 345 678',
            'consent' => '1',
        ], $overrides));
    }

    public function test_known_ap_shows_guest_capture_form(): void
    {
        $this->knownAp();

        $this->get('/portal?ap=11:22:33:44:55:66&id=aa:bb:cc:dd:ee:ff')
            ->assertOk()
            ->assertSee('name="first_name"', false)
            ->assertSee('name="phone"', false)
            ->assertSee('Email address')
            ->assertSee('(optional)', false)
            ->assertSee('Sunset Villa may store my details');
    }

    public function test_connect_captures_guest_with_phone_and_no_email(): void
    {
        $property = $this->knownAp();
        $this->mockUnifi();

        $this->connectAsGuest(['marketing_opt_in' => '1'])->assertOk()->assertSee("You're connected", false);

        $guest = Guest::sole();
        $this->assertSame($property->id, $guest->property_id);
        $this->assertSame('Wanjiru', $guest->first_name);
        $this->assertSame('+254712345678', $guest->phone);
        $this->assertNull($guest->email);
        $this->assertTrue($guest->marketing_opt_in);
        $this->assertNotNull($guest->consented_at);
        $this->assertStringContainsString('Sunset Villa', $guest->consent_text);
        $this->assertSame('aa:bb:cc:dd:ee:ff', $guest->device_mac);
        $this->assertSame(1, $guest->total_visits);
        $this->assertSame('WiFi', $guest->source);
    }

    public function test_same_phone_updates_existing_guest(): void
    {
        $this->knownAp();
        $this->mockUnifi(2);

        $this->connectAsGuest(['marketing_opt_in' => '1']);
        $this->travel(1)->days();
        $this->connectAsGuest(['id' => '00:11:22:33:44:55', 'phone' => '+254 712 345 678', 'email' => 'W@Example.com']);

        $guest = Guest::sole();
        $this->assertSame('w@example.com', $guest->email);
        $this->assertSame(2, $guest->total_visits);
        $this->assertTrue($guest->marketing_opt_in, 'opt-in is not withdrawn by an unticked box');
    }

    public function test_returning_device_connects_with_one_tap(): void
    {
        $this->knownAp();
        $this->mockUnifi(2);

        $this->connectAsGuest();
        $this->get('/portal?ap=11:22:33:44:55:66&id=AA:BB:CC:DD:EE:FF')
            ->assertSee('Welcome back, Wanjiru!')
            ->assertDontSee('name="phone"', false);

        $this->travel(1)->days();
        $this->post('/portal/connect', ['id' => 'aa:bb:cc:dd:ee:ff', 'ap' => '11:22:33:44:55:66'])->assertOk();

        $this->assertSame(2, Guest::sole()->total_visits);
    }

    public function test_reconnecting_within_a_visit_does_not_count_twice(): void
    {
        $this->knownAp();
        $this->mockUnifi(2);

        $this->connectAsGuest();
        $this->post('/portal/connect', ['id' => 'aa:bb:cc:dd:ee:ff', 'ap' => '11:22:33:44:55:66']);

        $this->assertSame(1, Guest::sole()->total_visits);
    }

    public function test_invalid_details_rerender_form_without_authorizing(): void
    {
        $this->knownAp();
        $this->mockUnifi(0);

        $this->connectAsGuest(['first_name' => '', 'phone' => '12', 'consent' => null, 'email' => 'nope'])
            ->assertStatus(422)
            ->assertSee('Please add your first name.')
            ->assertSee('Enter a valid WhatsApp number')
            ->assertSee('Please tick the box to agree before connecting.')
            ->assertSee('The email field must be a valid email address.');

        $this->assertSame(0, Guest::count());
    }

    public function test_unknown_ap_connects_without_capturing(): void
    {
        $this->mockUnifi();

        $this->post('/portal/connect', ['id' => 'aa:bb:cc:dd:ee:ff', 'ap' => '99:99:99:99:99:99'])->assertOk();

        $this->assertSame(0, Guest::count());
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
