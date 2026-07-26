<?php

use App\Models\Setting;
use App\Models\User;

it('redirects unpaid hosts to billing when billing is enabled and stripe key is present', function () {
    config(['services.stripe.key' => 'pk_test_xxx']);
    Setting::setValue('billing_enabled', 'enabled', 'billing', 'string');

    $host = User::factory()->host()->create();

    $response = $this->actingAs($host)->get(route('host.dashboard'));

    $response->assertRedirect(route('host.billing.index'));
});

it('allows unpaid hosts to access dashboard when billing is disabled', function () {
    config(['services.stripe.key' => null]);
    Setting::setValue('billing_enabled', 'disabled', 'billing', 'string');

    $host = User::factory()->host()->create();

    $response = $this->actingAs($host)->get(route('host.dashboard'));

    $response->assertOk();
});

it('allows subscribed hosts to access dashboard when billing is enabled', function () {
    config(['services.stripe.key' => 'pk_test_xxx']);
    Setting::setValue('billing_enabled', 'enabled', 'billing', 'string');

    $host = User::factory()->host()->create();
    $host->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_xxx',
        'stripe_status' => 'active',
        'quantity' => 1,
    ]);

    $response = $this->actingAs($host)->get(route('host.dashboard'));

    $response->assertOk();
});

it('allows hosts to simulate mpesa when billing is disabled', function () {
    config(['services.stripe.key' => null]);
    Setting::setValue('billing_enabled', 'disabled', 'billing', 'string');

    $host = User::factory()->host()->create();

    $response = $this->actingAs($host)->post(route('host.billing.simulate'));

    $response->assertRedirect();
    expect($host->subscriptions()->where('type', 'default')->exists())->toBeTrue();
});

it('prevents mpesa simulation when billing is explicitly enabled', function () {
    config(['services.stripe.key' => 'pk_test_xxx']);
    Setting::setValue('billing_enabled', 'enabled', 'billing', 'string');

    $host = User::factory()->host()->create();

    $response = $this->actingAs($host)->post(route('host.billing.simulate'));

    $response->assertRedirect();
    $response->assertSessionHas('error');
});
