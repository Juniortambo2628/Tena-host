<?php

use App\Http\Controllers\CallbackController;
use App\Mail\SignupAlertMail;
use App\Models\Registration;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    Mail::fake();
    Setting::setValue('signup_alert_emails', 'team@example.com');
});

it('is switched off until Admin -> Settings turns it on', function () {
    $this->postJson('/api/callbacks', ['phone' => '712345678', 'kind' => 'hosts'])->assertNotFound();
    $this->get('/')->assertInertia(fn ($page) => $page->where('callbacks', false));

    Setting::setValue('callbacks_enabled', '1');
    $this->get('/')->assertInertia(fn ($page) => $page->where('callbacks', true));
});

it('stores a call-back request as a lead and alerts the team like a sign-up', function () {
    Setting::setValue('callbacks_enabled', '1');

    $this->postJson('/api/callbacks', ['phone' => '0712 345 678', 'kind' => 'events', 'page' => 'home'])->assertCreated();

    $lead = Registration::sole();
    expect($lead->phone)->toBe('+254712345678')
        ->and($lead->type)->toBe('business')
        ->and($lead->source_page)->toBe('callback')
        ->and($lead->referral_source)->toBe('Call-back form')
        ->and($lead->answers['callbackFor'])->toBe('events')
        ->and($lead->consent_text)->toContain('call or WhatsApp you');
    Mail::assertSent(SignupAlertMail::class);
});

it('accepts only Kenyan mobile numbers', function (string $phone, ?string $e164) {
    expect(CallbackController::kenyanMobile($phone))->toBe($e164);
})->with([
    ['712345678', '+254712345678'],
    ['+254 712 345 678', '+254712345678'],
    ['0112345678', '+254112345678'],
    ['254712345678', '+254712345678'],
    ['71234567', null],          // 8 digits
    ['612345678', null],         // not a mobile prefix
    ['+44 7123 456789', null],
]);

it('rejects bad input and keeps an existing lead intact', function () {
    Setting::setValue('callbacks_enabled', '1');
    $this->postJson('/api/callbacks', ['phone' => '12345', 'kind' => 'hosts'])->assertJsonValidationErrors('phone');
    $this->postJson('/api/callbacks', ['phone' => '712345678', 'kind' => 'spaceport'])->assertJsonValidationErrors('kind');

    Registration::create(['type' => 'host', 'first_name' => 'Amina', 'last_name' => 'K', 'phone' => '+254712345678', 'property_type' => 'other', 'status' => 'active', 'source_page' => 'hosts']);
    $this->postJson('/api/callbacks', ['phone' => '712345678', 'kind' => 'hosts'])->assertOk();

    $lead = Registration::sole();
    expect($lead->first_name)->toBe('Amina')->and($lead->source_page)->toBe('hosts')->and($lead->answers['source'])->toBe('callback');
});

it('throttles repeated requests from one visitor', function () {
    Setting::setValue('callbacks_enabled', '1');
    foreach (range(1, 5) as $i) {
        $this->postJson('/api/callbacks', ['phone' => '71234567'.$i, 'kind' => 'hosts']);
    }
    $this->postJson('/api/callbacks', ['phone' => '712345670', 'kind' => 'hosts'])->assertStatus(429);
});

it('records callback_submit events', function () {
    $this->postJson('/api/track', ['event' => 'callback_submit', 'page' => 'home'])->assertSuccessful();
});
