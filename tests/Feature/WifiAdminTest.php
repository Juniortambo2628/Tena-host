<?php

use App\Models\AccessPoint;
use App\Models\Property;
use App\Models\Setting;
use App\Models\User;
use App\Models\WifiEvent;
use App\Services\PropertyMonitorService;
use App\Services\Unifi\UnifiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function unifiTest(): UnifiService
{
    return new UnifiService(baseUrl: 'https://controller.test', username: 'admin', password: 'secret', site: 'default');
}

function configureUnifi(): void
{
    Setting::setValue('unifi_base_url', 'https://controller.test', 'unifi');
    Setting::setValue('unifi_username', 'admin', 'unifi');
    Setting::setValue('unifi_password', Crypt::encryptString('secret'), 'unifi');
}

it('walks through every check when the controller is set up right', function () {
    Http::fake([
        'controller.test/api/auth/login' => Http::response([], 200),
        'controller.test/proxy/network/api/s/default/self' => Http::response(['data' => []], 200),
        'controller.test/proxy/network/api/s/default/stat/device' => Http::response(['data' => [['mac' => 'aa'], ['mac' => 'bb']]], 200),
        'controller.test/*' => Http::response('', 302),
    ]);

    $result = unifiTest()->diagnose();

    expect($result['ok'])->toBeTrue()
        ->and($result['devices'])->toBe(2)
        ->and(collect($result['steps'])->pluck('ok')->all())->toBe([true, true, true, true, true]);
});

it('says what to fix when the login is refused, and skips the later checks', function () {
    Http::fake([
        'controller.test/api/auth/login' => Http::response([], 401),
        'controller.test/*' => Http::response('', 200),
    ]);

    $result = unifiTest()->diagnose();
    $steps = collect($result['steps'])->keyBy('key');

    expect($result['ok'])->toBeFalse()
        ->and($steps['login']['ok'])->toBeFalse()
        ->and($steps['login']['hint'])->toContain('Wrong username or password')
        ->and($steps['site']['ok'])->toBeNull()
        ->and($result['message'])->toStartWith('Log in:');
});

it('points at the site ID when the site can’t be opened', function () {
    Http::fake([
        'controller.test/api/auth/login' => Http::response([], 200),
        'controller.test/proxy/network/api/s/default/self' => Http::response([], 404),
        'controller.test/*' => Http::response('', 200),
    ]);

    $steps = collect(unifiTest()->diagnose()['steps'])->keyBy('key');

    expect($steps['site']['ok'])->toBeFalse()->and($steps['site']['hint'])->toContain('site ID');
});

it('asks for the details before testing anything', function () {
    $result = (new UnifiService(baseUrl: '', username: '', password: ''))->diagnose();

    expect($result['ok'])->toBeFalse()->and($result['steps'][0]['ok'])->toBeFalse()->and($result['steps'][1]['ok'])->toBeNull();
});

it('logs guests let online or refused, with the site they were at', function () {
    $property = Property::factory()->create();
    AccessPoint::factory()->create(['property_id' => $property->id, 'mac_address' => '11:22:33:44:55:66']);
    Http::fake([
        '*api/auth/login' => Http::sequence()->push([], 200)->push([], 200),
        '*cmd/stamgr' => Http::sequence()->push(['meta' => ['rc' => 'ok']], 200)->push(['meta' => ['rc' => 'error', 'msg' => 'api.err.UnknownStation']], 200),
    ]);

    expect(unifiTest()->authorizeGuest('aa:bb:cc:dd:ee:ff', '11:22:33:44:55:66'))->toBeTrue()
        ->and(unifiTest()->authorizeGuest('aa:bb:cc:dd:ee:01', '11:22:33:44:55:66'))->toBeFalse();

    expect(WifiEvent::orderBy('id')->get(['type', 'level', 'property_id'])->toArray())->toBe([
        ['type' => 'guest_authorized', 'level' => 'info', 'property_id' => $property->id],
        ['type' => 'guest_refused', 'level' => 'error', 'property_id' => $property->id],
    ])->and(WifiEvent::where('type', 'guest_refused')->value('message'))->toContain('api.err.UnknownStation');
});

it('logs access points going offline, and a failing check only once an hour', function () {
    $property = Property::factory()->create(['name' => 'The Loft']);
    AccessPoint::factory()->create(['property_id' => $property->id, 'mac_address' => '11:22:33:44:55:66', 'name' => 'Lounge AP', 'status' => 'online', 'last_seen' => now()]);
    $this->mock(UnifiService::class, function ($mock) {
        $mock->shouldReceive('isConfigured')->andReturnTrue();
        $mock->shouldReceive('devices')->once()->andReturn([['mac' => '11:22:33:44:55:66', 'online' => false, 'last_seen' => now()->subMinutes(20), 'clients' => 0]]);
        $mock->shouldReceive('devices')->andThrow(new RuntimeException('cURL error 28: Connection timed out after 10001 ms'));
    });
    $monitor = app(PropertyMonitorService::class);

    $monitor->syncFromUnifi();
    $monitor->syncFromUnifi();
    $monitor->syncFromUnifi();

    expect(WifiEvent::where('type', 'ap_offline')->value('message'))->toBe('Lounge AP at The Loft is offline.')
        ->and(WifiEvent::where('type', 'device_sync_failed')->count())->toBe(1)
        ->and(WifiEvent::where('type', 'device_sync_failed')->value('message'))->toContain('Connection timed out after 10001 ms')
        ->and(Setting::getValue('unifi_last_sync_at'))->not->toBeNull();
});

it('gives the admin the setup, health, access points and log', function () {
    configureUnifi();
    $property = Property::factory()->create(['name' => 'The Loft']);
    AccessPoint::factory()->create(['property_id' => $property->id, 'status' => 'online', 'last_seen' => now()]);
    AccessPoint::factory()->create(['property_id' => $property->id, 'last_seen' => null]);
    WifiEvent::record('guest_authorized', 'A device was let online.', ['device' => 'aa:bb'], 'info', $property->id);
    WifiEvent::record('guest_refused', 'Refused.', [], 'error', $property->id);

    $this->actingAs(User::factory()->admin()->create())->get(route('admin.wifi.edit'))
        ->assertInertia(fn ($page) => $page->component('Admin/Settings/Unifi')
            ->where('status.configured', true)
            ->where('status.authorized24h', 1)
            ->where('status.refused24h', 1)
            ->where('status.aps', ['total' => 2, 'online' => 1, 'offline' => 0, 'neverSeen' => 1])
            ->where('status.lastError.message', 'Refused.')
            ->has('accessPoints', 2)
            ->where('events.0.label', 'Guest not connected')
            ->where('events.1.property', 'The Loft')
            ->where('portal_url', url('/portal'))
            ->has('eventTypes'));
});

it('runs the step-by-step test, remembers the result and logs it', function () {
    configureUnifi();
    Http::fake(['controller.test/api/auth/login' => Http::response([], 401), 'controller.test/*' => Http::response('', 200)]);

    $this->actingAs(User::factory()->admin()->create())->postJson(route('admin.wifi.test'), ['password' => ''])
        ->assertOk()->assertJsonPath('ok', false)->assertJsonPath('steps.2.key', 'login');

    expect(json_decode(Setting::getValue('unifi_last_test'), true)['ok'])->toBeFalse()
        ->and(WifiEvent::where('type', 'connection_test')->value('level'))->toBe('error');
});

it('checks access points on demand, and keeps the WiFi admin to admins', function () {
    $host = User::factory()->host()->create();
    $this->actingAs($host)->get(route('admin.wifi.edit'))->assertForbidden();
    $this->actingAs($host)->post(route('admin.wifi.sync'))->assertForbidden();

    $admin = User::factory()->admin()->create();
    $this->actingAs($admin)->post(route('admin.wifi.sync'))->assertSessionHas('error', 'Connect a controller first.');

    configureUnifi();
    Http::fake([
        '*api/auth/login' => Http::response([], 200),
        '*stat/device' => Http::response(['data' => []], 200),
    ]);
    $this->actingAs($admin)->post(route('admin.wifi.sync'))->assertSessionHas('success', '0 access points checked.');
});

it('keeps the log for 30 days', function () {
    WifiEvent::record('guest_authorized', 'Old.');
    WifiEvent::query()->update(['created_at' => now()->subDays(31)]);
    WifiEvent::record('guest_authorized', 'New.');

    $this->artisan('model:prune', ['--model' => [WifiEvent::class]]);

    expect(WifiEvent::pluck('message')->all())->toBe(['New.']);
});
