<?php

use App\Mail\TransferUpdateMail;
use App\Models\Campaign;
use App\Models\DeviceAgreement;
use App\Models\Guest;
use App\Models\Property;
use App\Models\PropertyTransfer;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Setting::setValue('property_transfers_enabled', '1');
    Mail::fake();
    $this->owner = User::factory()->host()->create(['email' => 'owner@example.com']);
    $this->newOwner = User::factory()->host()->create(['email' => 'new.owner@example.com']);
    $this->admin = User::factory()->admin()->create();
    $this->property = Property::factory()->create(['user_id' => $this->owner->id]);
});

/** Offer, accept and hand over a property; returns the completed transfer. */
function completeTransfer(array $offer = [], array $accept = []): PropertyTransfer
{
    test()->actingAs(test()->owner)->post(route('host.transfers.store'), $offer + ['property_id' => test()->property->id, 'email' => 'new.owner@example.com', 'password' => 'password'])->assertSessionHasNoErrors();
    $transfer = PropertyTransfer::latest('id')->firstOrFail();
    test()->actingAs(test()->newOwner)->post(route('host.transfers.accept', $transfer), $accept)->assertSessionHasNoErrors();
    if ($transfer->fresh()->held_at) {
        test()->actingAs(test()->admin)->post(route('admin.transfers.release', $transfer))->assertSessionHasNoErrors();
    }
    test()->travel(PropertyTransfer::COOLING_OFF_HOURS * 60 + 1)->minutes();
    test()->artisan('transfers:complete')->assertSuccessful();

    return tap($transfer->fresh(), fn ($t) => expect($t->status)->toBe('completed'));
}

function reverseTransfer(PropertyTransfer $transfer, array $data = [])
{
    return test()->actingAs(test()->admin)->post(route('admin.transfers.reverse', $transfer), $data + ['note' => 'Ticket 1042: both owners agreed by email', 'agreed' => true]);
}

it('moves the property and everything with it back to the previous owner', function () {
    $guest = Guest::factory()->create(['property_id' => $this->property->id, 'consented_at' => now(), 'marketing_opt_in' => true, 'email' => 'amina@example.com']);
    $transfer = completeTransfer();
    $campaign = Campaign::factory()->create(['user_id' => $this->newOwner->id, 'property_id' => $this->property->id]);
    $member = User::factory()->host()->create(['team_owner_id' => $this->newOwner->id, 'team_role' => 'manager']);
    DB::table('staff_property')->insert(['user_id' => $member->id, 'property_id' => $this->property->id]);

    reverseTransfer($transfer)->assertSessionHasNoErrors();

    $transfer->refresh();
    expect($this->property->fresh()->user_id)->toBe($this->owner->id)
        ->and($campaign->fresh()->user_id)->toBe($this->owner->id)
        ->and($guest->fresh()->campaign_hold_until)->toBeNull()
        ->and(DB::table('staff_property')->where('property_id', $this->property->id)->exists())->toBeFalse()
        ->and($transfer->status)->toBe('reversed')
        ->and($transfer->reversed_by)->toBe($this->admin->id)
        ->and($transfer->admin_note)->toContain('Ticket 1042')
        ->and($transfer->notice_status)->toBe('stopped')
        ->and($transfer->moved['reversed']['campaigns'])->toBe(1);

    // No "new owner" notices go out after it was undone.
    $this->artisan('transfers:notices')->assertSuccessful();
    expect($transfer->fresh()->notice_sent)->toBe(0);

    Mail::assertSent(TransferUpdateMail::class, fn ($mail) => $mail->hasTo('owner@example.com') && $mail->heading === 'Your property is back');
    Mail::assertSent(TransferUpdateMail::class, fn ($mail) => $mail->hasTo('new.owner@example.com') && $mail->heading === 'A property transfer was reversed');

    // The previous owner sees it again; the new owner no longer does.
    $this->actingAs($this->owner)->get(route('host.transfers.index'))
        ->assertInertia(fn ($page) => $page->where('properties.0.id', $this->property->id));
    $this->actingAs($this->newOwner)->get(route('host.transfers.index'))
        ->assertInertia(fn ($page) => $page->where('properties', []));
});

it('gives back the device payments the new owner took over', function () {
    $agreement = DeviceAgreement::record([
        'user_id' => $this->owner->id, 'property_id' => $this->property->id, 'device_type' => 'pro',
        'price_kes' => 42000, 'monthly_kes' => 5000, 'months' => 12, 'starts_on' => now()->toDateString(),
    ]);
    $transfer = completeTransfer(['device_takeover' => true], ['takeover' => true]);
    expect($agreement->fresh()->user_id)->toBe($this->newOwner->id);

    reverseTransfer($transfer)->assertSessionHasNoErrors();

    expect($agreement->fresh()->user_id)->toBe($this->owner->id)
        ->and($agreement->fresh()->events()->first()->type)->toBe('transfer_reversed')
        ->and($transfer->fresh()->moved['reversed']['devices'])->toBe(1);
});

it('needs a note and both owners’ written agreement, and only admins can do it', function () {
    $transfer = completeTransfer();

    $this->actingAs($this->owner)->post(route('admin.transfers.reverse', $transfer), ['note' => 'x', 'agreed' => true])->assertForbidden();
    reverseTransfer($transfer, ['note' => '', 'agreed' => false])->assertSessionHasErrors(['note', 'agreed']);

    expect($transfer->fresh()->status)->toBe('completed')
        ->and($this->property->fresh()->user_id)->toBe($this->newOwner->id);
});

it('can only be reversed within 30 days, and not after the property moved on again', function () {
    $transfer = completeTransfer();

    $this->actingAs($this->admin)->get(route('admin.transfers.index'))
        ->assertInertia(fn ($page) => $page->where('transfers.0.reversible', true));

    // The new owner has since offered it to someone else.
    Property::whereKey($this->property->id)->update(['user_id' => User::factory()->host()->create()->id]);
    reverseTransfer($transfer)->assertSessionHasErrors('transfer');
    Property::whereKey($this->property->id)->update(['user_id' => $this->newOwner->id]);

    $this->travel(PropertyTransfer::REVERSE_DAYS + 1)->days();
    reverseTransfer($transfer)->assertSessionHasErrors('transfer');
    expect($transfer->fresh()->status)->toBe('completed');
});

it('can’t reverse a transfer that never completed', function () {
    $this->actingAs($this->owner)->post(route('host.transfers.store'), ['property_id' => $this->property->id, 'email' => 'new.owner@example.com', 'password' => 'password']);

    reverseTransfer(PropertyTransfer::firstOrFail())->assertSessionHasErrors('transfer');
});
