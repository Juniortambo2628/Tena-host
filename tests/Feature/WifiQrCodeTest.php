<?php

use App\Models\Property;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Setting::setValue('billing_enabled', 'disabled', 'billing', 'string');
    $this->host = User::factory()->host()->create();
    $this->property = Property::factory()->create(['user_id' => $this->host->id, 'wifi_ssid' => 'Sunrise Guest']);
});

it('keeps the WiFi QR code hidden until an admin switches it on', function () {
    $this->actingAs($this->host)->get(route('host.properties.show', $this->property))
        ->assertInertia(fn ($page) => $page->component('Host/Properties/Show')->where('wifiQrEnabled', false));
});

it('shows the WiFi QR code on the site page once switched on', function () {
    Setting::setValue('wifi_qr_enabled', '1', 'general', 'boolean');

    $this->actingAs($this->host)->get(route('host.properties.show', $this->property))
        ->assertInertia(fn ($page) => $page->where('wifiQrEnabled', true)
            ->where('property.wifi_ssid', 'Sunrise Guest'));
});

it('lets an admin switch the WiFi QR code on and off', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.settings.update'), ['settings' => ['wifi_qr_enabled' => '1']])
        ->assertSessionHas('success');
    expect(Setting::getValue('wifi_qr_enabled'))->toBe('1');

    $this->actingAs($admin)->post(route('admin.settings.update'), ['settings' => ['wifi_qr_enabled' => '0']]);
    expect(Setting::getValue('wifi_qr_enabled'))->toBe('0');
});

it('does not show another host’s site', function () {
    Setting::setValue('wifi_qr_enabled', '1', 'general', 'boolean');
    $other = User::factory()->host()->create();

    $this->actingAs($other)->get(route('host.properties.show', $this->property))->assertForbidden();
});
