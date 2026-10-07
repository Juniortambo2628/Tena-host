<?php

namespace Tests\Feature;

use App\Models\AccessPoint;
use App\Models\Guest;
use App\Models\LandingSection;
use App\Models\Notification;
use App\Models\Property;
use App\Models\User;
use App\Services\Cms\PageBlueprint;
use App\Services\Unifi\UnifiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PropertyAlertsTest extends TestCase
{
    use RefreshDatabase;

    private Property $property;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.whatsapp' => [
            'driver' => 'whatsapp_cloud', 'token' => 't', 'phone_number_id' => '1', 'template' => null,
            'language' => 'en', 'api_version' => 'v21.0', 'fallback_to_sms' => false,
        ]]);
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.1']]])]);

        $host = User::factory()->create(['role' => 'host', 'phone_number' => '+254700000001']);
        $this->property = Property::factory()->create(['user_id' => $host->id, 'name' => 'Sunset Villa', 'occupancy_threshold' => 4]);
    }

    private function ap(array $attributes = []): AccessPoint
    {
        return AccessPoint::factory()->create(array_merge([
            'property_id' => $this->property->id, 'name' => 'Lobby', 'mac_address' => '11:22:33:44:55:66',
            'status' => 'online', 'last_seen' => now(),
        ], $attributes));
    }

    public function test_outage_is_reported_once_then_recovery(): void
    {
        $ap = $this->ap(['last_seen' => now()->subMinutes(30)]);

        $this->artisan('alerts:check')->assertSuccessful();
        $this->artisan('alerts:check');

        $this->assertSame(1, Notification::where('type', 'outage_alert')->count());
        Http::assertSent(fn (Request $r) => $r['to'] === '254700000001' && str_contains($r['text']['body'], 'The WiFi at Sunset Villa (Lobby) has been offline'));

        $ap->fresh()->update(['status' => 'online', 'last_seen' => now()]);
        $this->artisan('alerts:check');

        $this->assertSame(1, Notification::where('type', 'outage_resolved')->count());
        $this->assertNull($ap->fresh()->outage_alerted_at);
    }

    public function test_healthy_and_never_seen_aps_raise_nothing(): void
    {
        $this->ap();
        $this->ap(['mac_address' => 'aa:aa:aa:aa:aa:aa', 'last_seen' => null, 'status' => 'offline']);

        $this->artisan('alerts:check');

        Http::assertNothingSent();
    }

    public function test_unifi_status_drives_outages(): void
    {
        $this->ap();
        $this->mock(UnifiService::class, function ($mock) {
            $mock->shouldReceive('isConfigured')->andReturnTrue();
            $mock->shouldReceive('devices')->andReturn([
                ['mac' => '11:22:33:44:55:66', 'online' => false, 'last_seen' => now()->subMinutes(20), 'clients' => 0],
            ]);
        });

        $this->artisan('alerts:check');

        $this->assertSame('offline', AccessPoint::sole()->status);
        $this->assertSame(1, Notification::where('type', 'outage_alert')->count());
    }

    public function test_occupancy_alert_when_more_guests_than_the_limit(): void
    {
        Guest::factory()->count(5)->create(['property_id' => $this->property->id, 'last_connected' => now()->subHours(2)]);

        $this->artisan('alerts:check');
        $this->artisan('alerts:check'); // once a day

        $this->assertSame(1, Notification::where('type', 'occupancy_alert')->count());
        Http::assertSent(fn (Request $r) => str_contains($r['text']['body'], '5 people have connected to the WiFi at Sunset Villa') && str_contains($r['text']['body'], 'Your limit is 4'));
    }

    public function test_alert_features_are_no_longer_coming_soon(): void
    {
        PageBlueprint::setFeatureStatus('outage_alerts', 'coming_soon');
        $this->assertSame('coming_soon', $this->featureStatus('outage_alerts'));

        PageBlueprint::setFeatureStatus('outage_alerts', 'live');
        $this->assertSame('live', $this->featureStatus('outage_alerts'));
        $this->assertSame('live', $this->featureStatus('occupancy_alerts'));
    }

    private function featureStatus(string $feature): ?string
    {
        $contents = LandingSection::where('section_key', 'feature_status')->sole()->contents()->pluck('value', 'content_key');
        $index = collect($contents)->search($feature);

        return $contents[str_replace('.key', '.status', (string) $index)] ?? null;
    }

    public function test_no_occupancy_alerts_for_businesses_or_within_the_limit(): void
    {
        Guest::factory()->count(4)->create(['property_id' => $this->property->id, 'last_connected' => now()->subHour()]);
        $this->artisan('alerts:check');

        $this->property->host->update(['account_type' => 'business']);
        Guest::factory()->count(3)->create(['property_id' => $this->property->id, 'last_connected' => now()->subHour()]);
        $this->artisan('alerts:check');

        $this->assertSame(0, Notification::where('type', 'occupancy_alert')->count());
    }
}
