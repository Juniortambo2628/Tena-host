<?php

namespace Tests\Feature;

use App\Models\AccessPoint;
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

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
