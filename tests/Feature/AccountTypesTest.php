<?php

use App\Models\Registration;
use App\Models\Setting;
use App\Models\User;
use App\Services\MonthlyReportService;
use App\Services\SignupConversionService;
use App\Support\AccountType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    Mail::fake();
    Http::fake();
});

it('defines each account type’s words in one place', function () {
    expect(AccountType::for('host'))->toMatchArray(['people' => 'guests', 'places' => 'properties', 'splash' => 'stays'])
        ->and(AccountType::for('business'))->toMatchArray(['people' => 'customers', 'places' => 'locations', 'splash' => 'business'])
        ->and(AccountType::for('event'))->toMatchArray(['label' => 'Events', 'people' => 'attendees', 'places' => 'venues', 'splash' => 'business'])
        ->and(AccountType::for('nonsense')['type'])->toBe('host');
});

it('shares the account’s words with the dashboard', function () {
    $organiser = User::factory()->host()->create(['account_type' => User::ACCOUNT_EVENT]);

    $this->actingAs($organiser)->get(route('host.dashboard'))
        ->assertInertia(fn ($page) => $page->where('auth.account.people', 'attendees')->where('auth.account.places', 'venues'));
});

it('turns event enquiries into event accounts only once switched on', function () {
    $enquiry = fn () => Registration::factory()->create([
        'type' => 'event', 'first_name' => 'Achieng', 'email' => null, 'phone' => '+2547'.random_int(10000000, 99999999),
        'business_name' => null, 'units' => null, 'answers' => ['eventName' => 'Kona Design Week'], 'status' => 'active',
    ]);
    $convert = fn () => app(SignupConversionService::class)->convert($enquiry(), ['whatsapp'])['user'];

    expect($convert()->account_type)->toBe(User::ACCOUNT_BUSINESS);

    Setting::setValue('event_accounts_enabled', '1');
    $organiser = $convert();
    expect($organiser->account_type)->toBe(User::ACCOUNT_EVENT)
        ->and($organiser->properties()->sole()->name)->toBe('Kona Design Week');
});

it('lets an admin create, find and change event accounts', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->host()->create(['account_type' => User::ACCOUNT_BUSINESS]);

    $this->actingAs($admin)->post(route('admin.users.store'), [
        'first_name' => 'Achieng', 'last_name' => 'O', 'email' => 'achieng@example.com', 'role' => 'event',
    ])->assertSessionHasNoErrors();
    $organiser = User::where('email', 'achieng@example.com')->sole();
    expect([$organiser->role, $organiser->account_type])->toBe(['host', User::ACCOUNT_EVENT]);

    $this->actingAs($admin)->get(route('admin.hosts.index', ['type' => 'event']))
        ->assertInertia(fn ($page) => $page->has('hosts.data', 1)->where('hosts.data.0.email', 'achieng@example.com')
            ->where('filters.type', 'event')->where('typeCounts.business', 1)->where('typeCounts.event', 1));

    $this->actingAs($admin)->put(route('admin.hosts.update', $organiser), ['account_type' => 'event', 'account_profile' => 'venue'])
        ->assertSessionHasNoErrors();
    expect($organiser->fresh()->account_profile)->toBe('venue');

    $this->actingAs($admin)->put(route('admin.hosts.update', $organiser), ['account_profile' => 'stadium'])
        ->assertSessionHasErrors('account_profile');
});

it('lets a business group its sites into regions', function () {
    $business = User::factory()->host()->create(['account_type' => User::ACCOUNT_BUSINESS]);

    $this->actingAs($business)->post(route('host.properties.store'), [
        'name' => 'Kahawa House Westlands', 'region' => 'Nairobi branches', 'occupancy_threshold' => 20,
    ])->assertSessionHasNoErrors();

    expect($business->properties()->sole()->region)->toBe('Nairobi branches');
});

it('words the monthly report for each account', function () {
    $organiser = User::factory()->host()->create(['account_type' => User::ACCOUNT_EVENT]);
    $summary = app(MonthlyReportService::class)->summary($organiser, ['month' => 'October 2026', 'new_guests' => 120, 'returning_guests' => 30] + array_fill_keys(['messages_sent', 'reviews_requested', 'reviews_opened'], 0));

    expect($summary)->toContain('120 new attendees')->not->toContain('guests');
});
