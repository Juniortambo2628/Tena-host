<?php

use App\Http\Controllers\PublicPageController;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Setting::setValue('v2_live', '1', 'general', 'boolean');
});

it('is the live site unless switched off', function () {
    Setting::where('key', 'v2_live')->delete();
    cache()->forget('app_settings');

    expect(PublicPageController::v2Live())->toBeTrue();
});

it('serves the v2 homepage at /, indexed, with links that stay off /v2', function () {
    $this->get('/')->assertOk()
        ->assertInertia(fn ($page) => $page->component('Public/V2/Home')
            ->where('v2Base', '')
            ->where('seo.index', true)
            ->where('seo.url', url('/')));
});

it('serves the v2 audience pages at /hosts, /business and /events', function (string $audience) {
    $this->get("/{$audience}")->assertOk()
        ->assertInertia(fn ($page) => $page->component('Public/V2/Audience')
            ->where('audience', $audience)
            ->where('v2Base', '')
            ->where('seo.index', true)
            ->where('others', fn ($others) => collect($others)->every(fn ($o) => ! str_starts_with($o['to'], '/v2'))));
})->with(['hosts', 'business', 'events']);

it('sends the old /v2 addresses to the live ones', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->get('/v2')->assertRedirect('/');
    $this->get('/v2/hosts')->assertRedirect('/hosts');
});

it('brings the previous design back when switched off', function () {
    Setting::setValue('v2_live', '0', 'general', 'boolean');

    $this->get('/')->assertInertia(fn ($page) => $page->component('Public/Page'));
    $this->get('/hosts')->assertInertia(fn ($page) => $page->component('Public/Page'));
});

it('saves the switch from Admin -> Settings', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.settings.update'), ['settings' => ['v2_live' => '0']])
        ->assertSessionHasNoErrors();

    expect(Setting::getValue('v2_live'))->toBe('0');
});
