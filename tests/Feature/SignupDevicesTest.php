<?php

use App\Models\LandingContent;
use App\Models\LandingPage;
use App\Models\Registration;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

function deviceSignup(array $overrides = []): array
{
    return array_merge([
        'type' => 'business',
        'firstName' => 'Otieno',
        'phone' => '+254 722 000 111',
        'businessName' => 'Kahawa House',
        'plan' => 'starter',
        'consent' => true,
    ], $overrides);
}

function signupFields(string $slug): LandingContent
{
    return LandingContent::whereHas('section', fn ($q) => $q->where('section_key', 'signup')->whereHas('page', fn ($p) => $p->where('slug', $slug)))
        ->where('content_key', 'steps.1.fields')->firstOrFail();
}

it('asks which device and how to pay, just before the plan, on both sign-up forms', function (string $slug) {
    $keys = array_column(json_decode(signupFields($slug)->value, true), 'key');

    expect(array_slice($keys, array_search('device', $keys, true), 3))->toBe(['device', 'devicePayment', 'plan']);
})->with(['hosts', 'business']);

it('stores the device and how they will pay for it', function () {
    Mail::fake();

    $this->postJson(route('signups.store'), deviceSignup(['device' => 'pro', 'devicePayment' => 'monthly']))->assertCreated();

    expect(Registration::first()->answers)->toMatchArray(['device' => 'pro', 'devicePayment' => 'monthly', 'plan' => 'starter']);
});

it('leaves the device questions optional, and refuses a device that is not offered', function () {
    Mail::fake();

    $this->postJson(route('signups.store'), deviceSignup())->assertCreated();
    $this->postJson(route('signups.store'), deviceSignup(['device' => 'gold', 'phone' => '+254 733 000 222']))
        ->assertStatus(422)->assertJsonValidationErrors('device');
});

it('gives the sign-up form the device prices from /pricing', function () {
    $this->get('/business')->assertInertia(fn ($page) => $page->where('devices', [
        ['id' => 'lite', 'name' => 'TenaFi Lite', 'price_kes' => 25000, 'monthly_kes' => 3000, 'months' => 12],
        ['id' => 'pro', 'name' => 'TenaFi Pro', 'price_kes' => 42000, 'monthly_kes' => 5000, 'months' => 12],
        ['id' => 'outdoor', 'name' => 'TenaFi Outdoor', 'price_kes' => 48000, 'monthly_kes' => 5500, 'months' => 12],
    ]));
});

it('adds the device questions to a live form that lacks them, keeping its other questions', function () {
    Storage::fake('local');
    // The live form as it is today: no device questions, and an admin's own extra question.
    $row = signupFields('business');
    $fields = collect(json_decode($row->value, true))->reject(fn ($f) => in_array($f['key'], ['device', 'devicePayment'], true))->values()->all();
    $fields[] = ['key' => 'parking', 'label' => 'Parking?', 'type' => 'text', 'required' => false, 'options' => [], 'placeholder' => '', 'hint' => ''];
    $row->update(['value' => json_encode($fields, JSON_UNESCAPED_UNICODE)]);
    $before = $row->fresh()->value;
    LandingPage::clearCache();

    $this->artisan('tenafi:pricing-devices --dry-run')
        ->expectsOutputToContain('[business/signup] steps.1.fields')
        ->expectsOutputToContain('Which TenaFi device? / How would you like to pay for it?')
        ->assertSuccessful();

    $this->artisan('tenafi:pricing-devices')->assertSuccessful();
    $keys = array_column(json_decode($row->fresh()->value, true), 'key');
    expect(array_slice($keys, array_search('device', $keys, true), 3))->toBe(['device', 'devicePayment', 'plan'])
        ->and($keys)->toContain('parking');

    // Signing up now accepts the device.
    Mail::fake();
    $this->postJson(route('signups.store'), deviceSignup(['device' => 'lite', 'devicePayment' => 'upfront']))->assertCreated();

    $backup = basename(Storage::disk('local')->files('cms-backups')[0]);
    $this->artisan("tenafi:pricing-devices --restore={$backup}")->assertSuccessful();
    expect($row->fresh()->value)->toBe($before);
});
