<?php

use App\Models\Amenity;
use App\Models\Guest;
use App\Models\MpesaTransaction;
use App\Models\Order;
use App\Models\Property;
use App\Models\User;
use App\Services\Billing\PlanPricing;
use App\Services\MpesaService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/*
 * The M-Pesa STK callbacks are public URLs. A callback changes a payment only
 * when the URL's token and the CheckoutRequestID both match the prompt, the
 * paid amount equals the prompt, and it hasn't been applied before.
 */

beforeEach(function () {
    Mail::fake();
    $this->host = User::factory()->host()->create(['phone_number' => '+254700000001']);
    $this->quote = app(PlanPricing::class)->quote('starter', 1, 0, 'monthly');
    $this->token = MpesaService::newCallbackToken();
    $this->transaction = MpesaTransaction::create([
        'user_id' => $this->host->id, 'MerchantRequestID' => 'MR-1', 'CheckoutRequestID' => 'ws_CO_1',
        'Amount' => $this->quote['total'], 'PhoneNumber' => '254712345678', 'Status' => 'pending',
        'meta' => $this->quote, 'callback_token' => $this->token,
    ]);
});

function stkBody(string $checkoutId, int|float $amount, int $resultCode = 0): array
{
    return ['Body' => ['stkCallback' => [
        'MerchantRequestID' => 'MR-1', 'CheckoutRequestID' => $checkoutId, 'ResultCode' => $resultCode, 'ResultDesc' => 'OK',
        'CallbackMetadata' => ['Item' => [
            ['Name' => 'Amount', 'Value' => $amount],
            ['Name' => 'MpesaReceiptNumber', 'Value' => 'QKX123'],
            ['Name' => 'PhoneNumber', 'Value' => 254712345678],
        ]],
    ]]];
}

function extrasOrder(): Order
{
    $host = User::factory()->host()->create();
    $property = Property::factory()->create(['user_id' => $host->id]);
    $amenity = Amenity::factory()->create(['property_id' => $property->id, 'price' => 1500, 'is_active' => true]);
    $guest = Guest::factory()->create(['property_id' => $property->id]);

    return Order::create([
        'guest_id' => $guest->id, 'property_id' => $property->id, 'amenity_id' => $amenity->id, 'status' => 'pending',
        'total' => 1500, 'payment_status' => 'pending', 'payer_phone' => '+254712345678',
        'mpesa_checkout_request_id' => 'ws_CO_X', 'mpesa_callback_token' => test()->token,
    ]);
}

it('sends each prompt with its own token in the callback URL', function () {
    $url = MpesaService::callbackUrl('mpesa.extras.callback', $this->token);

    expect($url)->toBe(route('mpesa.extras.callback').'/'.$this->token)
        ->and(MpesaService::newCallbackToken())->not->toBe($this->token)->toHaveLength(64);
});

it('changes nothing without the token or with a wrong one', function (?string $token) {
    $this->postJson(route('mpesa.callback', $token), stkBody('ws_CO_1', $this->quote['total']))
        ->assertOk()->assertExactJson(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);

    expect($this->transaction->fresh()->Status)->toBe('pending')
        ->and($this->host->fresh()->subscribed('default'))->toBeFalse();
})->with([
    'no token' => [null],
    'wrong token' => [str_repeat('a', 64)],
]);

it('changes nothing with the right token but another CheckoutRequestID', function () {
    $this->postJson(route('mpesa.callback', $this->token), stkBody('ws_CO_OTHER', $this->quote['total']))
        ->assertExactJson(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);

    expect($this->transaction->fresh()->Status)->toBe('pending')
        ->and($this->host->fresh()->subscribed('default'))->toBeFalse();
});

it('applies a callback with the right token and CheckoutRequestID', function () {
    $this->postJson(route('mpesa.callback', $this->token), stkBody('ws_CO_1', $this->quote['total']))->assertOk();

    expect($this->transaction->fresh())->Status->toBe('completed')->MpesaReceiptNumber->toBe('QKX123')
        ->and($this->host->fresh()->subscribed('default'))->toBeTrue();
});

it('records a payment that does not equal the prompt as failed', function (int $offset) {
    $this->postJson(route('mpesa.callback', $this->token), stkBody('ws_CO_1', $this->quote['total'] + $offset))->assertOk();

    expect($this->transaction->fresh())->Status->toBe('failed')->MpesaReceiptNumber->toBe('QKX123')
        ->and($this->host->fresh()->subscribed('default'))->toBeFalse();
    Mail::assertNothingSent();
})->with(['less' => [-1], 'more' => [1]]);

it('applies a repeated callback once', function () {
    $this->postJson(route('mpesa.callback', $this->token), stkBody('ws_CO_1', $this->quote['total']))->assertOk();
    $endsAt = $this->host->fresh()->subscription('default')->ends_at;

    $this->postJson(route('mpesa.callback', $this->token), stkBody('ws_CO_1', $this->quote['total']))->assertOk();
    // A late "cancelled" copy can't undo a completed payment either.
    $this->postJson(route('mpesa.callback', $this->token), stkBody('ws_CO_1', $this->quote['total'], resultCode: 1032))->assertOk();

    expect($this->transaction->fresh()->Status)->toBe('completed')
        ->and($this->host->fresh()->subscription('default')->ends_at->equalTo($endsAt))->toBeTrue()
        ->and($this->host->subscriptions()->count())->toBe(1);
});

it('logs only the result code and our id, never the phone number', function () {
    $logged = [];
    Log::listen(function ($message) use (&$logged) {
        $logged[] = $message->message.' '.json_encode($message->context);
    });

    $this->postJson(route('mpesa.callback', $this->token), stkBody('ws_CO_1', $this->quote['total']));
    $this->postJson(route('mpesa.callback', str_repeat('b', 64)), stkBody('ws_CO_1', $this->quote['total']));
    $order = extrasOrder();
    $this->postJson(route('mpesa.extras.callback', $this->token), stkBody('ws_CO_X', 1500));

    $all = implode("\n", $logged);
    expect($all)->toContain('"result_code":0')
        ->toContain($this->transaction->id)
        ->toContain('"order":'.$order->id)
        ->not->toContain('254712345678')
        ->not->toContain('QKX123');
});

it('takes the old token-less URL only for prompts sent before tokens, for a short while', function () {
    $legacy = MpesaTransaction::create([
        'user_id' => $this->host->id, 'MerchantRequestID' => 'MR-1', 'CheckoutRequestID' => 'ws_CO_OLD',
        'Amount' => $this->quote['total'], 'PhoneNumber' => '254712345678', 'Status' => 'pending', 'meta' => $this->quote,
    ]);

    // A prompt with a token can't be answered on the old URL.
    $this->postJson(route('mpesa.callback'), stkBody('ws_CO_1', $this->quote['total']))->assertOk();
    expect($this->transaction->fresh()->Status)->toBe('pending');

    $this->travel(MpesaService::LEGACY_CALLBACK_MINUTES + 1)->minutes();
    $this->postJson(route('mpesa.callback'), stkBody('ws_CO_OLD', $this->quote['total']))->assertOk();
    expect($legacy->fresh()->Status)->toBe('pending');

    $this->travelBack();
    $legacy->forceFill(['created_at' => now()])->save();
    $this->postJson(route('mpesa.callback'), stkBody('ws_CO_OLD', $this->quote['total']))->assertOk();
    expect($legacy->fresh()->Status)->toBe('completed');
});

describe('extras orders', function () {
    it('changes nothing without the right token or CheckoutRequestID', function () {
        $order = extrasOrder();

        $this->postJson(route('mpesa.extras.callback'), stkBody('ws_CO_X', 1500))->assertExactJson(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
        $this->postJson(route('mpesa.extras.callback', str_repeat('c', 64)), stkBody('ws_CO_X', 1500))->assertOk();
        $this->postJson(route('mpesa.extras.callback', $this->token), stkBody('ws_CO_OTHER', 1500))->assertOk();

        expect($order->fresh()->payment_status)->toBe('pending');
    });

    it('marks an overpayment failed and a matching repeated callback paid once', function () {
        $order = extrasOrder();
        $this->postJson(route('mpesa.extras.callback', $this->token), stkBody('ws_CO_X', 1501))->assertOk();
        expect($order->fresh()->payment_status)->toBe('failed');

        $order->refresh()->update(['payment_status' => 'pending']);
        $this->postJson(route('mpesa.extras.callback', $this->token), stkBody('ws_CO_X', 1500))->assertOk();
        $paidAt = $order->fresh()->paid_at;
        $this->travel(5)->minutes();
        $this->postJson(route('mpesa.extras.callback', $this->token), stkBody('ws_CO_X', 1500, resultCode: 1032))->assertOk();

        expect($order->fresh())->payment_status->toBe('paid')
            ->and($order->fresh()->paid_at->equalTo($paidAt))->toBeTrue();
    });

    it('never sends the token to the browser', function () {
        expect(extrasOrder()->toArray())->not->toHaveKey('mpesa_callback_token')
            ->and($this->transaction->toArray())->not->toHaveKey('callback_token');
    });
});
