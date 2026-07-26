<?php

use App\Models\Setting;
use App\Models\User;

it('allows an admin to update billing_enabled setting', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post(route('admin.settings.update'), [
        'settings' => [
            'billing_enabled' => 'disabled',
        ],
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');
    expect(Setting::getValue('billing_enabled'))->toBe('disabled');
});

it('allows an admin to update site settings with typed values', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post(route('admin.settings.update'), [
        'settings' => [
            'site_name' => 'Tena Test',
            'maintenance_mode' => '1',
            'support_email' => 'help@tena.test',
        ],
    ]);

    $response->assertRedirect();
    expect(Setting::getValue('site_name'))->toBe('Tena Test');
    expect(Setting::getValue('maintenance_mode', false))->toBeTrue();
    expect(Setting::getValue('support_email'))->toBe('help@tena.test');
});

it('prevents non-admins from updating settings', function () {
    $host = User::factory()->host()->create();

    $response = $this->actingAs($host)->post(route('admin.settings.update'), [
        'settings' => [
            'site_name' => 'Hacked',
        ],
    ]);

    $response->assertForbidden();
});

it('allows a user to update their profile first and last name', function () {
    $user = User::factory()->create([
        'first_name' => 'Old',
        'last_name' => 'Name',
        'email' => 'old@example.com',
    ]);

    $response = $this->actingAs($user)->patch(route('profile.update'), [
        'first_name' => 'New',
        'last_name' => 'User',
        'email' => 'new@example.com',
    ]);

    $response->assertRedirect(route('profile.edit'));

    $user->refresh();
    expect($user->first_name)->toBe('New');
    expect($user->last_name)->toBe('User');
    expect($user->email)->toBe('new@example.com');
});

it('validates profile update fields', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->patch(route('profile.update'), [
        'first_name' => '',
        'last_name' => '',
        'email' => 'not-an-email',
    ]);

    $response->assertSessionHasErrors(['first_name', 'last_name', 'email']);
});
