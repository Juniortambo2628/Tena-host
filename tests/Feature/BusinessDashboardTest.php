<?php

use App\Models\Guest;
use App\Models\Property;
use App\Models\Setting;
use App\Models\User;
use App\Services\BusinessInsights;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    Setting::setValue('billing_enabled', 'disabled', 'billing', 'string');
    $this->travelTo(Carbon::parse('2026-11-18 12:00:00'));
    $this->owner = User::factory()->host()->create(['account_type' => User::ACCOUNT_BUSINESS]);
    $this->westlands = Property::factory()->create(['user_id' => $this->owner->id, 'name' => 'Westlands', 'region' => 'Nairobi']);
    $this->nakuru = Property::factory()->create(['user_id' => $this->owner->id, 'name' => 'Nakuru', 'region' => 'Rift Valley']);

    // New this month, came once, asked for a review that was opened.
    Guest::factory()->create(['property_id' => $this->westlands->id, 'created_at' => '2026-11-10 09:00', 'last_connected' => '2026-11-10 09:30', 'total_visits' => 1,
        'consented_at' => now(), 'marketing_opt_in' => true, 'review_requested_at' => '2026-11-10 12:00', 'review_clicked_at' => '2026-11-10 13:00']);
    // A regular from before this month who came back on a Saturday evening, birthday treat sent this year.
    Guest::factory()->create(['property_id' => $this->westlands->id, 'created_at' => '2026-08-01 10:00', 'last_connected' => '2026-11-14 19:15', 'total_visits' => 6,
        'consented_at' => now(), 'marketing_opt_in' => false, 'birthday_sent_year' => 2026]);
    // Upcountry, an old customer who hasn't been back.
    Guest::factory()->create(['property_id' => $this->nakuru->id, 'created_at' => '2026-03-01 10:00', 'last_connected' => '2026-03-01 10:30', 'total_visits' => 1, 'consented_at' => null]);
});

it('gives businesses their own dashboard numbers', function () {
    $insights = app(BusinessInsights::class)->for($this->owner);

    expect($insights['summary'])->toMatchArray([
        'customers' => 3, 'newThisMonth' => 1, 'returningThisMonth' => 1, 'visits' => 8,
        'repeatRate' => 33.0, 'optInRate' => 50.0, 'reviewRequests' => 1, 'reviewClicks' => 1, 'birthdayTreats' => 1, 'sites' => 2,
    ]);

    $westlands = collect($insights['sites'])->firstWhere('name', 'Westlands');
    expect($westlands)->toMatchArray(['region' => 'Nairobi', 'customers' => 2, 'newThisMonth' => 1, 'repeatRate' => 50.0, 'reviewRequests' => 1, 'network' => 'none'])
        ->and(collect($insights['regions'])->pluck('customers', 'region')->all())->toBe(['Nairobi' => 2, 'Rift Valley' => 1]);

    // Busy times count each customer's latest connection in the last 90 days.
    expect(collect($insights['busyDays'])->pluck('customers', 'name')->only(['Tue', 'Sat'])->all())->toBe(['Tue' => 1, 'Sat' => 1])
        ->and(collect($insights['busyHours'])->firstWhere('name', '19:00')['customers'])->toBe(1);
});

it('shows the business home to business and events accounts, not rentals', function () {
    $this->actingAs($this->owner)->get(route('host.dashboard'))
        ->assertInertia(fn ($page) => $page->where('business.summary.customers', 3)->has('business.sites', 2));

    $host = User::factory()->host()->create();
    $this->actingAs($host)->get(route('host.dashboard'))->assertInertia(fn ($page) => $page->where('business', null));
});

it('gives a manager only their sites’ numbers', function () {
    $manager = User::factory()->host()->create(['account_type' => User::ACCOUNT_BUSINESS, 'team_owner_id' => $this->owner->id, 'team_role' => 'manager']);
    $manager->staffProperties()->sync([$this->nakuru->id]);

    $insights = app(BusinessInsights::class)->for($manager);

    expect($insights['summary']['customers'])->toBe(1)
        ->and(collect($insights['sites'])->pluck('name')->all())->toBe(['Nakuru'])
        ->and($insights['regions'])->toBe([]);
});
