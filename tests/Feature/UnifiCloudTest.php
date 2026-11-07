<?php

use App\Models\AccessPoint;
use App\Models\Property;
use App\Models\Setting;
use App\Models\User;
use App\Models\WifiEvent;
use App\Services\Unifi\UnifiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

uses(RefreshDatabase::class);

const CLOUD_NET = 'api.ui.com/v1/connector/consoles/CONSOLE1/proxy/network/integration';

function cloudUnifi(?string $console = 'CONSOLE1'): UnifiService
{
    return new UnifiService(site: 'default', apiKey: 'key-123', consoleId: $console);
}

function fakeCloudSite(array $extra = []): void
{
    Http::fake($extra + [
        'api.ui.com/v1/hosts' => Http::response(['data' => [['id' => 'CONSOLE1', 'type' => 'console', 'reportedState' => ['name' => 'OKJ Gateway']]]]),
        CLOUD_NET.'/v1/sites*' => Http::response(['data' => [['id' => 'site-uuid', 'internalReference' => 'default', 'name' => 'Testing_Purposes']]]),
    ]);
}

beforeEach(fn () => Sleep::fake());

it('lets a guest online through UniFi’s cloud with the API key', function () {
    fakeCloudSite([
        CLOUD_NET.'/v1/sites/site-uuid/clients/client-uuid/actions' => Http::response(['action' => 'AUTHORIZE_GUEST_ACCESS', 'grantedAuthorization' => []]),
        CLOUD_NET.'/v1/sites/site-uuid/clients*' => Http::response(['data' => [['id' => 'client-uuid', 'macAddress' => 'aa:bb:cc:dd:ee:ff', 'type' => 'WIRELESS']]]),
    ]);

    expect(cloudUnifi()->authorizeGuest('AA-BB-CC-DD-EE-FF', null, 60))->toBeTrue();

    Http::assertSent(fn ($r) => str_contains($r->url(), '/clients?filter=') && str_contains(urldecode($r->url()), "macAddress.eq('aa:bb:cc:dd:ee:ff')") && $r->header('X-API-Key') === ['key-123']);
    Http::assertSent(fn ($r) => str_ends_with($r->url(), '/clients/client-uuid/actions')
        && $r['action'] === 'AUTHORIZE_GUEST_ACCESS' && $r['timeLimitMinutes'] === 60);
    expect(WifiEvent::where('type', 'guest_authorized')->exists())->toBeTrue();
});

it('logs a refusal when the device isn’t connected to the site', function () {
    fakeCloudSite([CLOUD_NET.'/v1/sites/site-uuid/clients*' => Http::response(['data' => []])]);

    expect(cloudUnifi()->authorizeGuest('aa:bb:cc:dd:ee:ff'))->toBeFalse();

    Sleep::assertSleptTimes(1); // looked again once before giving up
    expect(WifiEvent::where('type', 'guest_refused')->value('message'))->toContain('isn\'t connected');
});

it('finds the only console on the key when no console ID is given', function () {
    fakeCloudSite([
        CLOUD_NET.'/v1/sites/site-uuid/devices*' => Http::response(['data' => [['id' => 'ap-uuid', 'macAddress' => 'A8:9C:6C:C4:D4:F7', 'state' => 'ONLINE']]]),
        CLOUD_NET.'/v1/sites/site-uuid/clients*' => Http::response(['data' => [['id' => 'c1', 'uplinkDeviceId' => 'ap-uuid'], ['id' => 'c2', 'uplinkDeviceId' => 'ap-uuid']]]),
    ]);

    $result = cloudUnifi(null)->diagnose();

    expect($result['ok'])->toBeTrue()
        ->and($result['devices'])->toBe(1)
        ->and(collect($result['steps'])->firstWhere('key', 'console')['detail'])->toContain('OKJ Gateway');

    expect(cloudUnifi()->devices())->toBe([['mac' => 'a8:9c:6c:c4:d4:f7', 'online' => true, 'last_seen' => null, 'clients' => 2]]);
});

it('says to make a new key when UniFi refuses it', function () {
    Http::fake(['api.ui.com/*' => Http::response(['code' => 'api.authentication.invalid', 'message' => 'Unauthorized'], 401)]);

    $result = cloudUnifi()->diagnose();
    $steps = collect($result['steps'])->keyBy('key');

    expect($result['ok'])->toBeFalse()
        ->and($steps['key']['ok'])->toBeFalse()
        ->and($steps['key']['hint'])->toContain('API Keys')
        ->and($steps['console']['ok'])->toBeNull();
});

it('says to update the console when it doesn’t offer the cloud API', function () {
    fakeCloudSite([CLOUD_NET.'/v1/sites*' => Http::response(['message' => 'Not Found'], 404)]);

    $steps = collect(cloudUnifi()->diagnose()['steps'])->keyBy('key');

    expect($steps['site']['ok'])->toBeFalse()
        ->and($steps['site']['hint'])->toContain('Update UniFi OS');
});

it('saves the cloud connection with the key encrypted and never sends the key back', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.wifi.update'), [
        'mode' => 'cloud', 'api_key' => 'key-123', 'console_id' => '', 'base_url' => '', 'username' => '', 'site' => 'default',
        'is_os' => true, 'verify_ssl' => false, 'auth_minutes' => 1440, 'timeout' => 15,
    ])->assertSessionHasNoErrors();

    expect(Setting::getValue('unifi_mode'))->toBe('cloud')
        ->and(Crypt::decryptString(Setting::getValue('unifi_api_key')))->toBe('key-123')
        ->and(UnifiService::fromSettings()->usesCloud())->toBeTrue()
        ->and(UnifiService::fromSettings()->isConfigured())->toBeTrue();

    $this->actingAs($admin)->get(route('admin.wifi.edit'))
        ->assertInertia(fn ($page) => $page->where('settings.mode', 'cloud')->where('settings.has_api_key', true)->missing('settings.api_key'));
});

it('asks for a key before switching to the cloud connection', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.wifi.update'), [
        'mode' => 'cloud', 'api_key' => '', 'site' => 'default', 'is_os' => true, 'verify_ssl' => false, 'auth_minutes' => 1440, 'timeout' => 15,
    ])->assertSessionHasErrors('api_key');

    expect(Setting::getValue('unifi_mode'))->toBeNull();
});

it('lets a guest online from the portal through the cloud', function () {
    Setting::setValue('unifi_mode', 'cloud', 'unifi');
    Setting::setValue('unifi_api_key', Crypt::encryptString('key-123'), 'unifi');
    Setting::setValue('unifi_console_id', 'CONSOLE1', 'unifi');
    fakeCloudSite([
        CLOUD_NET.'/v1/sites/site-uuid/clients/client-uuid/actions' => Http::response(['action' => 'AUTHORIZE_GUEST_ACCESS']),
        CLOUD_NET.'/v1/sites/site-uuid/clients*' => Http::response(['data' => [['id' => 'client-uuid', 'macAddress' => 'aa:bb:cc:dd:ee:ff']]]),
    ]);
    $property = Property::factory()->create();
    AccessPoint::factory()->create(['property_id' => $property->id, 'mac_address' => 'a8:9c:6c:c4:d4:f7']);

    expect(app(UnifiService::class)->authorizeGuest('aa:bb:cc:dd:ee:ff', 'a8:9c:6c:c4:d4:f7'))->toBeTrue()
        ->and(WifiEvent::where('type', 'guest_authorized')->value('property_id'))->toBe($property->id);
});
