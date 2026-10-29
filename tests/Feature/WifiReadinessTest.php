<?php

namespace Tests\Feature;

use App\Models\AccessPoint;
use App\Models\Property;
use App\Models\User;
use App\Services\Unifi\UnifiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WifiReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_host_saves_design_and_images_via_multipart_method_override(): void
    {
        Storage::fake('public');
        $host = User::factory()->create(['role' => 'host']);
        $property = Property::factory()->create(['user_id' => $host->id]);
        $this->actingAs($host)->post(route('host.properties.update', $property), [
            '_method' => 'patch', 'name' => 'Live test property',
            'branding_json' => ['template' => 'bold', 'accent' => '#123456', 'heading' => 'Hello guests'],
            'logo' => UploadedFile::fake()->image('logo.png'),
            'splash_image' => UploadedFile::fake()->image('cover.jpg'),
        ])->assertSessionHasNoErrors()->assertRedirect();

        $property->refresh();
        // First-pass template names are saved as their successors.
        $this->assertSame('nocturne', $property->branding_json['template']);
        Storage::disk('public')->assertExists(str_replace('/storage/', '', $property->logo_path));
        Storage::disk('public')->assertExists(str_replace('/storage/', '', $property->splash_image_path));
        $this->actingAs($host)->patch(route('host.properties.update', $property), [
            'name' => $property->name, 'branding_json' => ['template' => 'unknown', 'accent' => 'red;display:none'],
        ])->assertSessionHasErrors(['branding_json.template', 'branding_json.accent']);
    }

    public function test_ap_registration_normalizes_mac_and_rejects_duplicates_and_other_hosts(): void
    {
        $host = User::factory()->create(['role' => 'host']);
        $property = Property::factory()->create(['user_id' => $host->id]);
        $data = ['property_id' => $property->id, 'name' => 'Lobby', 'mac_address' => 'AA-BB-CC-DD-EE-FF'];
        $this->actingAs($host)->post(route('host.access-points.store'), $data)->assertSessionHasNoErrors();
        $this->assertSame('aa:bb:cc:dd:ee:ff', AccessPoint::sole()->mac_address);
        $data['mac_address'] = 'AABBCCDDEEFF';
        $this->post(route('host.access-points.store'), $data)->assertSessionHasErrors('mac_address');
        $data['mac_address'] = '11:22:33:44:55:66';
        $other = User::factory()->create(['role' => 'host']);
        $this->actingAs($other)->post(route('host.access-points.store'), $data)->assertForbidden();
    }

    public function test_unifi_http_success_with_api_error_is_not_authorization(): void
    {
        Http::fake([
            '*api/auth/login' => Http::response([], 200),
            '*cmd/stamgr' => Http::response(['meta' => ['rc' => 'error']], 200),
        ]);
        $unifi = new UnifiService(baseUrl: 'https://controller.test', username: 'test', password: 'test');
        $this->assertFalse($unifi->authorizeGuest('aa:bb:cc:dd:ee:ff', '11:22:33:44:55:66'));
    }

    public function test_host_sees_only_their_network_status(): void
    {
        $host = User::factory()->create(['role' => 'host']);
        $property = Property::factory()->create(['user_id' => $host->id]);
        AccessPoint::factory()->create(['property_id' => $property->id, 'status' => 'online']);
        AccessPoint::factory()->create(['property_id' => $property->id, 'status' => 'offline']);
        AccessPoint::factory()->create(['status' => 'online']);
        $this->actingAs($host)->get(route('host.dashboard'))->assertInertia(fn ($page) => $page->where('stats.onlineAPs', 1));
        $this->get(route('host.properties.show', $property))->assertInertia(fn ($page) => $page
            ->has('property.access_points', 2)->where('wifi.guests', 0)->where('wifi.visits', 0));
    }

    public function test_authorize_guest_speaks_the_unifi_os_and_classic_apis(): void
    {
        Http::fake([
            'udm.example/api/auth/login' => Http::response(['ok' => true], 200, ['X-CSRF-Token' => 'csrf-123']),
            'udm.example/proxy/network/api/s/villa/cmd/stamgr' => Http::response(['meta' => ['rc' => 'ok'], 'data' => []]),
            'classic.example:8443/api/login' => Http::response(['meta' => ['rc' => 'ok']]),
            'classic.example:8443/api/s/default/cmd/stamgr' => Http::response(['meta' => ['rc' => 'ok'], 'data' => []]),
        ]);

        $os = new UnifiService(baseUrl: 'https://udm.example', username: 'tenafi', password: 'secret', site: 'villa', isUnifiOs: true);
        $this->assertTrue($os->authorizeGuest('AA-BB-CC-DD-EE-FF', '112233445566', 60));

        Http::assertSent(fn ($r) => $r->url() === 'https://udm.example/api/auth/login' && $r['username'] === 'tenafi');
        Http::assertSent(fn ($r) => $r->url() === 'https://udm.example/proxy/network/api/s/villa/cmd/stamgr'
            && $r->hasHeader('X-CSRF-Token', 'csrf-123')
            && $r['cmd'] === 'authorize-guest' && $r['mac'] === 'aa:bb:cc:dd:ee:ff'
            && $r['ap_mac'] === '11:22:33:44:55:66' && $r['minutes'] === 60);

        $classic = new UnifiService(baseUrl: 'https://classic.example:8443', username: 'u', password: 'p', site: 'default', isUnifiOs: false);
        $this->assertTrue($classic->authorizeGuest('aa:bb:cc:dd:ee:ff'));
        Http::assertSent(fn ($r) => $r->url() === 'https://classic.example:8443/api/s/default/cmd/stamgr');
    }
}
