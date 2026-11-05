<?php

use App\Models\AccessPoint;
use App\Models\Event;
use App\Models\MpesaTransaction;
use App\Models\Notification;
use App\Models\Property;
use App\Models\Setting;
use App\Models\User;
use App\Services\Billing\PaystackVerifier;
use App\Services\MpesaService;
use App\Services\Unifi\UnifiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Billing is live and pay per event is on; the organiser has no plan.
    config(['services.paystack.public_key' => 'pk_test_xxx']);
    Setting::setValue('billing_enabled', 'enabled', 'billing', 'string');
    Setting::setValue('event_billing_enabled', '1', 'general', 'boolean');
    $this->travelTo(Carbon::parse('2026-12-05 10:00:00'));
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->organiser = User::factory()->host()->create(['account_type' => User::ACCOUNT_EVENT, 'phone_number' => '+254712345678']);
    $this->hall = Property::factory()->create(['user_id' => $this->organiser->id, 'name' => 'Kona Expo Hall']);
    AccessPoint::factory()->create(['property_id' => $this->hall->id, 'mac_address' => '11:22:33:44:55:66']);
    $this->event = Event::create([
        'user_id' => $this->organiser->id, 'property_id' => $this->hall->id, 'name' => 'Kona Design Week',
        'starts_on' => '2026-12-04', 'ends_on' => '2026-12-06',
    ]);
    $this->mock(UnifiService::class, function ($mock) {
        $mock->shouldReceive('authorizeGuest')->andReturnTrue();
        $mock->shouldReceive('normalizeMac')->andReturnUsing(fn ($m) => strtolower($m));
    });
});

function quoteEvent(int $amount = 15000): void
{
    test()->event->forceFill(['expected_attendees' => 300, 'quote_status' => 'quoted', 'quote_amount' => $amount, 'quoted_at' => now()])->save();
}

it('lets an organiser without a plan reach events, but nothing else', function () {
    $this->actingAs($this->organiser)->get(route('host.dashboard'))->assertRedirect(route('host.billing.index'));
    $this->actingAs($this->organiser)->get(route('host.guests.index'))->assertRedirect(route('host.billing.index'));
    $this->actingAs($this->organiser)->get(route('host.properties.index'))->assertOk(); // venues, to hold events
    $this->actingAs($this->organiser)->get(route('host.events.index'))->assertOk()
        ->assertInertia(fn ($page) => $page->where('events.0.needs_payment', true));
    $this->actingAs($this->organiser)->get(route('host.events.show', $this->event))->assertOk()
        ->assertInertia(fn ($page) => $page->where('payment.status', 'none')->where('payment.canPay', true)->where('messageUrl', null));

    // With the switch off, the plan is needed as before.
    Setting::setValue('event_billing_enabled', '0', 'general', 'boolean');
    $this->actingAs($this->organiser)->get(route('host.events.index'))->assertRedirect(route('host.billing.index'));
});

it('keeps an unpaid event off the WiFi page until it is paid', function () {
    $this->get('/portal?ap=11:22:33:44:55:66&id=aa:bb:cc:dd:ee:ff')->assertOk()->assertDontSee('Welcome to Kona Design Week');

    $this->event->markPaid();

    $this->get('/portal?ap=11:22:33:44:55:66&id=aa:bb:cc:dd:ee:ff')->assertOk()->assertSee('Welcome to Kona Design Week');
});

it('needs no payment from an organiser on a plan, or while the switch is off', function () {
    $this->organiser->subscriptions()->create(['type' => 'default', 'stripe_id' => 'sub_x', 'stripe_status' => 'active', 'quantity' => 1]);
    expect($this->event->fresh()->needsPayment())->toBeFalse();

    $this->organiser->subscriptions()->delete();
    expect($this->event->fresh()->needsPayment())->toBeTrue();
    Setting::setValue('event_billing_enabled', '0', 'general', 'boolean');
    expect($this->event->fresh()->needsPayment())->toBeFalse();
});

it('runs a quote from request to price to M-Pesa payment', function () {
    $this->actingAs($this->organiser)->post(route('host.events.quote', $this->event), ['expected_attendees' => 300, 'request_note' => 'Two halls'])
        ->assertSessionHasNoErrors();
    expect($this->event->fresh())->quote_status->toBe('requested')->expected_attendees->toBe(300)
        ->and(Notification::where('user_id', $this->admin->id)->where('type', 'event_quote_requested')->exists())->toBeTrue();

    // Can't pay before it's priced.
    $this->actingAs($this->organiser)->post(route('host.events.pay.mpesa', $this->event), ['phone_number' => '0712345678'])->assertNotFound();

    $this->actingAs($this->admin)->get(route('admin.event-quotes.index'))
        ->assertInertia(fn ($page) => $page->component('Admin/EventQuotes/Index')->where('events.0.status', 'requested'));
    $this->actingAs($this->admin)->put(route('admin.event-quotes.update', $this->event), ['quote_amount' => 15000, 'quote_note' => 'Covers 3 days'])
        ->assertSessionHasNoErrors();
    expect($this->event->fresh())->quote_status->toBe('quoted')->quote_amount->toBe(15000)
        ->and(Notification::where('user_id', $this->organiser->id)->where('type', 'event_quoted')->exists())->toBeTrue();

    // The STK push charges the server's price, and the callback marks the event paid (no plan extended).
    $this->mock(MpesaService::class, fn ($m) => $m->shouldReceive('initiateStkPush')->once()
        ->withArgs(fn ($phone, $amount) => $phone === '+254712345678' && (int) $amount === 15000)
        ->andReturn(['success' => true, 'data' => ['MerchantRequestID' => 'MR-1', 'CheckoutRequestID' => 'CR-1']]));
    $this->actingAs($this->organiser)->post(route('host.events.pay.mpesa', $this->event), ['phone_number' => '0712 345 678'])
        ->assertSessionHasNoErrors();

    $this->postJson(route('mpesa.callback'), ['Body' => ['stkCallback' => [
        'MerchantRequestID' => 'MR-1', 'ResultCode' => 0, 'ResultDesc' => 'OK',
        'CallbackMetadata' => ['Item' => [['Name' => 'Amount', 'Value' => 15000], ['Name' => 'MpesaReceiptNumber', 'Value' => 'RCPT1']]],
    ]]])->assertOk();

    expect($this->event->fresh())->quote_status->toBe('paid')->paid_at->not->toBeNull()
        ->and($this->organiser->fresh()->subscribed('default'))->toBeFalse()
        ->and(MpesaTransaction::sole()->Status)->toBe('completed');
});

it('does not mark the event paid when the M-Pesa amount falls short', function () {
    quoteEvent();
    MpesaTransaction::create(['user_id' => $this->organiser->id, 'MerchantRequestID' => 'MR-2', 'CheckoutRequestID' => 'CR-2', 'Amount' => 15000, 'PhoneNumber' => '+254712345678', 'Status' => 'pending', 'meta' => ['type' => 'event', 'event_id' => $this->event->id]]);

    $this->postJson(route('mpesa.callback'), ['Body' => ['stkCallback' => [
        'MerchantRequestID' => 'MR-2', 'ResultCode' => 0, 'ResultDesc' => 'OK',
        'CallbackMetadata' => ['Item' => [['Name' => 'Amount', 'Value' => 10], ['Name' => 'MpesaReceiptNumber', 'Value' => 'RCPT2']]],
    ]]])->assertOk();

    expect($this->event->fresh()->paid_at)->toBeNull();
});

it('pays by card once Paystack confirms the quoted amount, and only once per reference', function () {
    quoteEvent();
    $this->mock(PaystackVerifier::class, fn ($m) => $m->shouldReceive('verify')->with('ref_ok', 15000)->andReturnTrue());

    $this->actingAs($this->organiser)->post(route('host.events.pay.paystack', $this->event), ['reference' => 'ref_ok'])->assertSessionHas('success');
    expect($this->event->fresh()->paid_at)->not->toBeNull();

    // The same reference can't pay for a second event.
    $second = Event::create(['user_id' => $this->organiser->id, 'property_id' => $this->hall->id, 'name' => 'Second', 'starts_on' => '2026-12-20', 'ends_on' => '2026-12-20']);
    $second->forceFill(['quote_status' => 'quoted', 'quote_amount' => 15000])->save();
    $this->actingAs($this->organiser)->post(route('host.events.pay.paystack', $second), ['reference' => 'ref_ok'])->assertSessionHas('error');
    expect($second->fresh()->paid_at)->toBeNull();
});

it('keeps quotes to the organiser’s own events and admins', function () {
    quoteEvent();
    $other = User::factory()->host()->create(['account_type' => User::ACCOUNT_EVENT]);

    $this->actingAs($other)->post(route('host.events.quote', $this->event), ['expected_attendees' => 5])->assertForbidden();
    $this->actingAs($other)->post(route('host.events.pay.mpesa', $this->event), ['phone_number' => '0712345678'])->assertForbidden();
    $this->actingAs($this->organiser)->put(route('admin.event-quotes.update', $this->event), ['quote_amount' => 1])->assertForbidden();

    expect($this->event->fresh()->quote_amount)->toBe(15000);
});
