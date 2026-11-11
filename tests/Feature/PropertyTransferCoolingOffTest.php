<?php

use App\Mail\TransferUpdateMail;
use App\Models\DeviceAgreement;
use App\Models\Property;
use App\Models\PropertyTransfer;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Setting::setValue('property_transfers_enabled', '1');
    Mail::fake();
    $this->owner = User::factory()->host()->create(['email' => 'owner@example.com']);
    $this->newOwner = User::factory()->host()->create(['email' => 'new.owner@example.com']);
    $this->admin = User::factory()->admin()->create();
    $this->property = Property::factory()->create(['user_id' => $this->owner->id]);
});

function offerAndAccept(bool $accept = true): PropertyTransfer
{
    test()->actingAs(test()->owner)->post(route('host.transfers.store'), ['property_id' => test()->property->id, 'email' => 'new.owner@example.com', 'password' => 'password'])->assertSessionHasNoErrors();
    $transfer = PropertyTransfer::latest('id')->firstOrFail();
    if ($accept) {
        test()->actingAs(test()->newOwner)->post(route('host.transfers.accept', $transfer))->assertSessionHasNoErrors();
    }

    return $transfer->fresh();
}

/** The "It wasn't me" link from the last email to the owner that had one. */
function cancelLinkFromEmail(): string
{
    return Mail::sent(TransferUpdateMail::class, fn ($mail) => $mail->hasTo('owner@example.com') && $mail->cancel)->last()->cancel['url'];
}

function coolOff(): void
{
    test()->travel(PropertyTransfer::COOLING_OFF_HOURS * 60 + 1)->minutes();
    test()->artisan('transfers:complete')->assertSuccessful();
}

it('emails both sides at each step, with an "It wasn’t me" link for the owner', function () {
    $transfer = offerAndAccept(false);
    Mail::assertSent(TransferUpdateMail::class, fn ($mail) => $mail->hasTo('owner@example.com') && $mail->cancel !== null);
    Mail::assertSent(TransferUpdateMail::class, fn ($mail) => $mail->hasTo('new.owner@example.com') && $mail->button['url'] === route('host.transfers.index'));

    $this->actingAs($this->newOwner)->post(route('host.transfers.accept', $transfer));
    expect($transfer->fresh()->status)->toBe('accepted')
        ->and($transfer->fresh()->completes_at->diffInHours(now()->addHours(PropertyTransfer::COOLING_OFF_HOURS)) < 1)->toBeTrue();
    Mail::assertSent(TransferUpdateMail::class, fn ($mail) => $mail->hasTo('owner@example.com') && $mail->heading === 'Your property transfer was accepted' && $mail->cancel);

    coolOff();
    Mail::assertSent(TransferUpdateMail::class, fn ($mail) => $mail->hasTo('new.owner@example.com') && $mail->heading === 'The property is now yours');
    Mail::assertSent(TransferUpdateMail::class, fn ($mail) => $mail->hasTo('owner@example.com') && $mail->heading === 'Your property transfer is complete');

    // The email renders.
    expect((new TransferUpdateMail('Your property transfer was accepted', ['Line one.'], null, ['label' => 'Cancel this transfer', 'url' => 'https://example.com/x'], 'Amina'))->render())
        ->toContain('Hello Amina,')->toContain('Line one.')->toContain('Cancel this transfer');
});

it('lets the owner cancel during the cooling-off, and then nothing moves', function () {
    $transfer = offerAndAccept();

    $this->actingAs($this->owner)->post(route('host.transfers.cancel', $transfer))->assertSessionHasNoErrors();
    coolOff();

    expect($transfer->fresh()->status)->toBe('cancelled')
        ->and($transfer->fresh()->cancelled_by)->toBe('owner')
        ->and($this->property->fresh()->user_id)->toBe($this->owner->id);
});

it('cancels from the signed email link without signing in, after a confirm button', function () {
    $transfer = offerAndAccept();
    $link = cancelLinkFromEmail();
    auth()->logout();

    // Opening the link (as an email scanner would) only asks.
    $this->get($link)->assertOk()->assertSee('Cancel this transfer?');
    expect($transfer->fresh()->status)->toBe('accepted');

    // A tampered or unsigned link is refused.
    $this->post(route('transfers.cancel-link', $transfer))->assertForbidden();

    $this->post($link)->assertOk()->assertSee('Transfer cancelled');
    expect($transfer->fresh()->status)->toBe('cancelled')
        ->and($transfer->fresh()->cancelled_by)->toBe('link');

    coolOff();
    expect($this->property->fresh()->user_id)->toBe($this->owner->id);
});

it('lets an admin hold a transfer past its cooling-off, release it, or cancel one', function () {
    $transfer = offerAndAccept();

    $this->actingAs($this->owner)->post(route('admin.transfers.hold', $transfer))->assertForbidden();
    $this->actingAs($this->admin)->post(route('admin.transfers.hold', $transfer), ['note' => 'Checking with the owner'])->assertSessionHasNoErrors();

    coolOff();
    expect($this->property->fresh()->user_id)->toBe($this->owner->id)
        ->and($transfer->fresh()->admin_note)->toBe('Checking with the owner');

    $this->actingAs($this->admin)->get(route('admin.transfers.index'))->assertOk()
        ->assertInertia(fn ($page) => $page->component('Admin/Transfers/Index')->where('transfers.0.held', true)->where('auth.adminTransfers', true));

    $this->actingAs($this->admin)->post(route('admin.transfers.release', $transfer))->assertSessionHasNoErrors();
    $this->artisan('transfers:complete')->assertSuccessful();
    expect($this->property->fresh()->user_id)->toBe($this->newOwner->id);

    // Cancelling a waiting offer.
    $other = Property::factory()->create(['user_id' => $this->owner->id]);
    $this->actingAs($this->owner)->post(route('host.transfers.store'), ['property_id' => $other->id, 'email' => 'new.owner@example.com', 'password' => 'password']);
    $offer = PropertyTransfer::latest('id')->first();
    $this->actingAs($this->admin)->post(route('admin.transfers.cancel', $offer), ['note' => 'Owner asked by phone'])->assertSessionHasNoErrors();
    expect($offer->fresh()->status)->toBe('cancelled')
        ->and($offer->fresh()->cancelled_by)->toBe('admin');
});

it('stops the hand-over if a device starts being paid off during the cooling-off', function () {
    $transfer = offerAndAccept();
    DeviceAgreement::record([
        'user_id' => $this->owner->id, 'property_id' => $this->property->id, 'device_type' => 'pro',
        'price_kes' => 42000, 'monthly_kes' => 5000, 'months' => 12, 'starts_on' => now()->toDateString(),
    ]);

    coolOff();

    expect($transfer->fresh()->status)->toBe('cancelled')
        ->and($transfer->fresh()->cancelled_by)->toBe('system')
        ->and($this->property->fresh()->user_id)->toBe($this->owner->id);
});
