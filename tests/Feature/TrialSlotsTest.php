<?php

use App\Models\LandingContent;
use App\Models\LandingSection;
use App\Models\Registration;
use App\Models\Setting;
use App\Support\TrialSlots;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function signup(array $attributes = []): Registration
{
    return Registration::create($attributes + ['type' => 'host', 'first_name' => 'Amina', 'phone' => '+2547'.random_int(10000000, 99999999), 'property_type' => 'other', 'status' => 'active']);
}

it('counts real trial bookings against the total in Settings', function () {
    Setting::setValue('trial_slots_total', '50');
    signup();
    signup(['type' => 'business']);
    signup(['status' => 'converted']);
    signup(['status' => 'inactive']); // declined: frees the slot

    expect(TrialSlots::summary())->toBe(['taken' => 3, 'total' => 50, 'left' => 47]);

    // A new sign-up shows straight away (the cache is cleared on save).
    signup();
    expect(TrialSlots::summary()['taken'])->toBe(4);
});

it('only counts sign-ups since the trial opened, never goes past the total, and hides with no total', function () {
    Setting::setValue('trial_slots_total', '2');
    signup()->forceFill(['created_at' => now()->subMonths(3)])->save(); // the old waitlist
    Setting::setValue('trial_slots_since', now()->subWeek()->toDateString());
    TrialSlots::forget();
    signup();
    expect(TrialSlots::summary()['taken'])->toBe(1);

    signup();
    signup();
    expect(TrialSlots::summary())->toBe(['taken' => 2, 'total' => 2, 'left' => 0]);

    Setting::setValue('trial_slots_since', 'not a date');
    TrialSlots::forget();
    expect(TrialSlots::summary()['total'])->toBe(2);

    Setting::setValue('trial_slots_total', '0');
    expect(TrialSlots::summary())->toBeNull();
});

it('shares the counter and the WhatsApp button settings with every public page', function (string $url) {
    Setting::setValue('trial_slots_total', '50');
    signup();

    $this->get($url)->assertOk()->assertInertia(fn ($page) => $page
        ->where('trial', ['taken' => 1, 'total' => 50, 'left' => 49])
        ->where('site.whatsapp_button.content.number', '+254 703 501 597')
        ->where('site.whatsapp_button.content.message_hosts', fn ($m) => str_contains($m, 'short-term rental'))
        ->where('site.whatsapp_button.content.message_business', fn ($m) => str_contains($m, 'my business'))
        ->has('site.trial_slots.content.taken_text'));
})->with(['/', '/hosts', '/business']);

it('switches the counter on for pricing and sign-up without touching live copy', function () {
    $signup = LandingSection::whereHas('page', fn ($q) => $q->where('slug', 'hosts'))->where('section_key', 'signup')->firstOrFail();
    $signup->contents()->where('content_key', 'show_slots')->delete();
    $signup->contents()->where('content_key', 'title')->update(['value' => 'Live title']);

    (require database_path('migrations/2026_10_30_000004_whatsapp_button_and_trial_slots.php'))->up();

    expect($signup->contents()->where('content_key', 'show_slots')->value('value'))->toBe('1')
        ->and($signup->contents()->where('content_key', 'title')->value('value'))->toBe('Live title')
        ->and(LandingContent::where('section_id', $signup->id)->where('content_key', 'show_slots')->count())->toBe(1);
});

it('records WhatsApp clicks', function () {
    $this->postJson('/api/track', ['event' => 'whatsapp_click', 'page' => 'hosts'])->assertSuccessful();
});
