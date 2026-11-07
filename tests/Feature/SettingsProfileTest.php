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

it('never shares the two-factor secret or recovery codes with the page', function () {
    $user = User::factory()->host()->create([
        'two_factor_enabled' => true,
        'two_factor_secret' => 'JBSWY3DPEHPK3PXP',
        'two_factor_recovery_codes' => ['code-one', 'code-two'],
        'two_factor_confirmed_at' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('host.dashboard'));

    $response->assertOk()->assertInertia(fn ($page) => $page
        ->where('auth.user.id', $user->id)
        ->missing('auth.user.two_factor_secret')
        ->missing('auth.user.two_factor_recovery_codes'));
    $response->assertDontSee('JBSWY3DPEHPK3PXP');
    $response->assertDontSee('code-one');
});

it('rejects an invalid setting value and saves nothing from that request', function () {
    $admin = User::factory()->admin()->create();
    Setting::updateOrCreate(['key' => 'site_name'], ['value' => 'Before', 'type' => 'string', 'group' => 'general']);

    $response = $this->actingAs($admin)->post(route('admin.settings.update'), [
        'settings' => [
            'site_name' => 'After',
            'staff_wifi_enabled' => 'yes',
        ],
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors(['settings.staff_wifi_enabled']);
    expect(Setting::where('key', 'staff_wifi_enabled')->exists())->toBeFalse();
    expect(Setting::where('key', 'site_name')->value('value'))->toBe('Before');
});

it('rejects a signup alert webhook that is not a URL', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post(route('admin.settings.update'), [
        'settings' => ['signup_alert_webhook_url' => 'not a url'],
    ]);

    $response->assertSessionHasErrors(['settings.signup_alert_webhook_url']);
    expect(Setting::where('key', 'signup_alert_webhook_url')->exists())->toBeFalse();
});

it('still saves valid switch and webhook values', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post(route('admin.settings.update'), [
        'settings' => [
            'staff_wifi_enabled' => '1',
            'signup_alert_webhook_url' => 'https://hooks.example.com/signup',
        ],
    ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();
    expect(Setting::where('key', 'staff_wifi_enabled')->value('value'))->toBe('1');
    expect(Setting::where('key', 'signup_alert_webhook_url')->value('value'))->toBe('https://hooks.example.com/signup');
});

it('rejects a setting key it does not know and saves nothing from that request', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post(route('admin.settings.update'), [
        'settings' => [
            'site_name' => 'After',
            'mpesa_extras_enabled' => '0',
            'a.b' => 'x',
        ],
    ]);

    $response->assertSessionHasErrors(['settings.mpesa_extras_enabled']);
    expect(Setting::where('key', 'mpesa_extras_enabled')->exists())->toBeFalse();
    expect(Setting::where('key', 'a.b')->exists())->toBeFalse();
    expect(Setting::where('key', 'site_name')->value('value'))->not->toBe('After');
});

it('saves the full settings form as the admin page sends it, empty fields included', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post(route('admin.settings.update'), [
        'settings' => [
            'site_name' => 'TenaFi',
            'maintenance_mode' => '0',
            'support_email' => 'support@tena.com',
            'signup_alert_emails' => '',
            'signup_alert_webhook_url' => '',
            'trial_slots_total' => '50',
            'trial_slots_since' => '',
            'callbacks_enabled' => '0',
            'demo_enabled' => '0',
            'v2_pages_enabled' => '0',
            'event_accounts_enabled' => '0',
            'teams_enabled' => '0',
            'staff_wifi_enabled' => '1',
            'wifi_qr_enabled' => '0',
            'visitor_log_enabled' => '0',
            'event_billing_enabled' => '0',
            'email_primary_color' => '#000000',
            'email_accent_color' => '#FFD300',
            'business_address' => '',
            'welcome_email_heading' => '',
            'welcome_email_body' => '',
            'welcome_email_subject' => '',
            'receipt_email_heading' => '',
            'receipt_email_body' => '',
            'forgot_password_email_heading' => '',
            'forgot_password_email_body' => '',
            'waitlist_confirmation_subject' => '',
            'waitlist_confirmation_heading' => '',
            'waitlist_confirmation_body' => '',
            'waitlist_welcome_subject' => '',
            'waitlist_welcome_heading' => '',
            'waitlist_welcome_body' => '',
            'contact_enquiry_heading' => '',
            'contact_enquiry_body' => '',
            'billing_enabled' => 'auto',
        ],
    ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();
    expect(Setting::where('key', 'staff_wifi_enabled')->value('value'))->toBe('1');
});
