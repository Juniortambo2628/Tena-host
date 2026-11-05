<?php

use App\Mail\UserInvitationMail;
use App\Models\Guest;
use App\Models\Property;
use App\Models\Setting;
use App\Models\User;
use App\Services\MonthlyReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    Mail::fake();
    Http::fake();
    Setting::setValue('billing_enabled', 'disabled', 'billing', 'string');
    $this->owner = User::factory()->host()->create(['account_type' => User::ACCOUNT_BUSINESS]);
    $this->westlands = Property::factory()->create(['user_id' => $this->owner->id, 'name' => 'Westlands']);
    $this->karen = Property::factory()->create(['user_id' => $this->owner->id, 'name' => 'Karen']);
});

function inviteManager(array $overrides = []): User
{
    test()->actingAs(test()->owner)->post(route('host.team.store'), array_merge([
        'first_name' => 'Wanjiru', 'email' => 'wanjiru@example.com', 'team_role' => 'manager', 'sites' => [test()->westlands->id],
    ], $overrides))->assertSessionHasNoErrors();

    return User::where('email', $overrides['email'] ?? 'wanjiru@example.com')->sole();
}

/** What following the invite link and setting a password does (InvitationController). */
function acceptInvite(User $member): User
{
    $member->forceFill(['email_verified_at' => now()])->save();

    return $member;
}

it('is switched off until Admin -> Settings turns it on', function () {
    $this->actingAs($this->owner)->get(route('host.team.index'))->assertNotFound();
    $this->actingAs($this->owner)->get(route('host.dashboard'))->assertInertia(fn ($page) => $page->where('auth.team.enabled', false));
});

it('lets the owner invite a manager for some of their sites', function () {
    Setting::setValue('teams_enabled', '1');

    $manager = inviteManager();

    expect([$manager->role, $manager->team_role, $manager->team_owner_id, $manager->account_type])
        ->toBe(['host', 'manager', $this->owner->id, User::ACCOUNT_BUSINESS])
        ->and($manager->propertyIds())->toBe([$this->westlands->id]);
    Mail::assertSent(UserInvitationMail::class, fn ($mail) => $mail->hasTo('wanjiru@example.com'));

    $this->actingAs($this->owner)->get(route('host.team.index'))
        ->assertInertia(fn ($page) => $page->component('Host/Team/Index')->has('members', 1)->where('members.0.sites', [$this->westlands->id])->has('sites', 2));
});

it('shows a manager only their sites and keeps owner things to the owner', function () {
    Setting::setValue('teams_enabled', '1');
    $manager = acceptInvite(inviteManager());
    Guest::factory()->create(['property_id' => $this->westlands->id, 'first_name' => 'Seen']);
    Guest::factory()->create(['property_id' => $this->karen->id, 'first_name' => 'Hidden']);

    $this->actingAs($manager)->get(route('host.properties.index'))
        ->assertInertia(fn ($page) => $page->has('properties', 1)->where('properties.0.name', 'Westlands')->where('auth.team.member', true));
    $this->actingAs($manager)->get(route('host.guests.index'))
        ->assertInertia(fn ($page) => $page->where('guests.data', fn ($guests) => collect($guests)->pluck('first_name')->all() === ['Seen']));
    $this->actingAs($manager)->get(route('host.properties.show', $this->westlands))->assertOk();
    $this->actingAs($manager)->get(route('host.properties.show', $this->karen))->assertForbidden();

    // Adding or removing sites, billing, marketing and the team stay with the owner.
    $this->actingAs($manager)->post(route('host.properties.store'), ['name' => 'Mine', 'occupancy_threshold' => 20])->assertForbidden();
    $this->actingAs($manager)->delete(route('host.properties.destroy', $this->westlands))->assertForbidden();
    $this->actingAs($manager)->get(route('host.billing.index'))->assertForbidden();
    $this->actingAs($manager)->get(route('host.marketing.index'))->assertForbidden();
    $this->actingAs($manager)->get(route('host.team.index'))->assertForbidden();
});

it('lets a manager in on the owner’s plan', function () {
    Setting::setValue('teams_enabled', '1');
    $manager = acceptInvite(inviteManager());
    Setting::setValue('billing_enabled', 'enabled', 'billing', 'string');

    $this->actingAs($manager)->get(route('host.dashboard'))->assertRedirect(route('host.billing.index'));

    $this->owner->subscriptions()->create(['type' => 'default', 'stripe_id' => 'mpesa_X', 'stripe_status' => 'active', 'stripe_price' => 'basic_monthly', 'quantity' => 1, 'ends_at' => now()->addMonth()]);
    // A fresh login, as the next real request would be (the owner's subscriptions aren't cached on it).
    $this->actingAs($manager->fresh())->get(route('host.dashboard'))->assertOk();
});

it('only lets the owner hand out their own sites, and only edit their own team', function () {
    Setting::setValue('teams_enabled', '1');
    $other = User::factory()->host()->create();
    $theirs = Property::factory()->create(['user_id' => $other->id]);

    $this->actingAs($this->owner)->post(route('host.team.store'), [
        'first_name' => 'Sneaky', 'email' => 'sneaky@example.com', 'team_role' => 'manager', 'sites' => [$theirs->id],
    ])->assertSessionHasErrors('sites.0');

    $manager = inviteManager();
    $this->actingAs($other)->put(route('host.team.update', $manager), ['team_role' => 'manager', 'sites' => [$theirs->id]])->assertNotFound();
    $this->actingAs($other)->delete(route('host.team.destroy', $manager))->assertNotFound();
});

it('changes a member’s access and removes them', function () {
    Setting::setValue('teams_enabled', '1');
    $manager = inviteManager();

    $this->actingAs($this->owner)->put(route('host.team.update', $manager), ['team_role' => 'staff', 'sites' => [$this->karen->id]])
        ->assertSessionHasNoErrors();
    $manager->refresh();
    expect([$manager->role, $manager->team_role, $manager->propertyIds()])->toBe(['staff', 'staff', [$this->karen->id]]);

    $this->actingAs($this->owner)->delete(route('host.team.destroy', $manager))->assertSessionHasNoErrors();
    expect(User::find($manager->id))->toBeNull();
});

it('keeps team members out of the admin’s account list and monthly reports', function () {
    Setting::setValue('teams_enabled', '1');
    inviteManager();

    $this->actingAs(User::factory()->admin()->create())->get(route('admin.hosts.index'))
        ->assertInertia(fn ($page) => $page->has('hosts.data', 1)->where('typeCounts.business', 1));
    expect(app(MonthlyReportService::class)->recipients()->pluck('id')->all())->not->toContain(User::where('email', 'wanjiru@example.com')->value('id'));
});
