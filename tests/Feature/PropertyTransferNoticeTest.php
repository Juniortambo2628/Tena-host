<?php

use App\Mail\TransferNoticeMail;
use App\Models\Campaign;
use App\Models\Guest;
use App\Models\Property;
use App\Models\PropertyTransfer;
use App\Models\Setting;
use App\Models\User;
use App\Services\CampaignDispatcher;
use App\Services\ReviewRequestService;
use App\Services\TransferNoticeService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Setting::setValue('property_transfers_enabled', '1');
    Mail::fake();
    config(['services.whatsapp' => [
        'driver' => 'whatsapp_cloud', 'token' => 't', 'phone_number_id' => '1', 'template' => null,
        'language' => 'en', 'api_version' => 'v21.0', 'fallback_to_sms' => false,
    ]]);
    Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.1']]])]);

    $this->owner = User::factory()->host()->create();
    $this->newOwner = User::factory()->host()->create(['first_name' => 'Wanjiru', 'last_name' => 'Kamau', 'email' => 'wanjiru@example.com']);
    $this->property = Property::factory()->create(['user_id' => $this->owner->id, 'name' => 'Stay Awhile']);

    $consented = ['property_id' => $this->property->id, 'consented_at' => now()->subMonth(), 'marketing_opt_in' => true];
    $this->emailGuest = Guest::factory()->create($consented + ['email' => 'amina@example.com']);
    $this->phoneGuest = Guest::factory()->create($consented + ['email' => null]);
    $this->optedOut = Guest::factory()->create($consented + ['opted_out_at' => now()->subWeek()]);
    $this->noConsent = Guest::factory()->create(['property_id' => $this->property->id, 'consented_at' => null, 'check_out' => null]);
});

function acceptTransfer(): PropertyTransfer
{
    test()->actingAs(test()->owner)->post(route('host.transfers.store'), ['property_id' => test()->property->id, 'email' => 'wanjiru@example.com', 'password' => 'password'])->assertSessionHasNoErrors();
    $transfer = PropertyTransfer::latest('id')->firstOrFail();
    test()->actingAs(test()->newOwner)->post(route('host.transfers.accept', $transfer))->assertSessionHasNoErrors();

    return $transfer->fresh();
}

it('holds the transferred guests for 14 days and queues a notice for those who can be told', function () {
    $transfer = acceptTransfer();

    expect($transfer->notice_status)->toBe('pending')
        ->and($transfer->notice_total)->toBe(2) // not the opted-out guest, not the one who never agreed
        ->and($transfer->hold_until->isSameDay(now()->addDays(PropertyTransfer::CAMPAIGN_HOLD_DAYS)))->toBeTrue()
        ->and($this->emailGuest->fresh()->campaign_hold_until)->not->toBeNull()
        ->and($this->noConsent->fresh()->campaign_hold_until)->not->toBeNull();
});

it('tells each guest once, by email when they gave one and otherwise on WhatsApp', function () {
    $transfer = acceptTransfer();

    $this->artisan('transfers:notices')->assertSuccessful();

    Mail::assertSent(TransferNoticeMail::class, fn ($mail) => $mail->hasTo('amina@example.com') && $mail->newOwner === 'Wanjiru Kamau');
    Mail::assertSent(TransferNoticeMail::class, 1);
    Http::assertSent(fn ($request) => str_contains($request->url(), 'graph.facebook.com')
        && str_contains(json_encode($request->data()), 'now run by Wanjiru Kamau'));
    Http::assertSentCount(1);

    $transfer->refresh();
    expect($transfer->notice_status)->toBe('sent')
        ->and($transfer->notice_sent)->toBe(2)
        // The email's unsubscribe link needs the guest's token.
        ->and($this->emailGuest->fresh()->review_token)->not->toBeNull();

    // Running it again tells nobody twice.
    $this->artisan('transfers:notices')->assertSuccessful();
    Mail::assertSent(TransferNoticeMail::class, 1);

    // The email itself renders, in the venue's name, with an unsubscribe link.
    $guest = $this->emailGuest->fresh();
    $guest->setRelation('property', $this->property->fresh());
    expect((new TransferNoticeMail($guest, 'Wanjiru Kamau'))->render())
        ->toContain('Stay Awhile has a new owner')
        ->toContain('now run by Wanjiru Kamau')
        ->toContain('Unsubscribe');
});

it('sends in batches and picks up where it left off', function () {
    $transfer = acceptTransfer();
    $notices = app(TransferNoticeService::class);

    expect($notices->sendBatch($transfer, 1))->toBe(1)
        ->and($transfer->fresh()->notice_status)->toBe('pending');
    $notices->sendBatch($transfer->fresh(), 1);
    $notices->sendBatch($transfer->fresh(), 1);

    expect($transfer->fresh()->notice_status)->toBe('sent')
        ->and($transfer->fresh()->notice_sent)->toBe(2);
});

it('keeps campaigns and review requests away from the transferred guests until the hold ends', function () {
    acceptTransfer();
    $property = $this->property->fresh();
    $campaign = Campaign::factory()->create(['user_id' => $this->newOwner->id, 'property_id' => $property->id, 'type' => 'email', 'target_audience' => 'all_guests']);
    $audience = fn () => app(CampaignDispatcher::class)->audienceQuery($campaign)->pluck('id')->all();
    $reviewable = fn () => app(ReviewRequestService::class)->everyone($property)->pluck('id')->all();

    // A guest who joins after the transfer isn't held.
    $newGuest = Guest::factory()->create(['property_id' => $property->id, 'consented_at' => now(), 'marketing_opt_in' => true, 'email' => 'new@example.com']);

    expect($audience())->toBe([$newGuest->id])
        ->and($reviewable())->not->toContain($this->emailGuest->id);

    $this->travel(PropertyTransfer::CAMPAIGN_HOLD_DAYS + 1)->days();

    expect($audience())->toContain($this->emailGuest->id, $newGuest->id)
        ->and($audience())->not->toContain($this->optedOut->id)
        ->and($reviewable())->toContain($this->emailGuest->id);
});

it('marks a transfer with nobody to tell', function () {
    Guest::where('property_id', $this->property->id)->update(['opted_out_at' => now()]);

    expect(acceptTransfer()->notice_status)->toBe('none');
});
