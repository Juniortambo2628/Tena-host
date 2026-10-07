<?php

namespace Tests\Feature;

use App\Models\MpesaTransaction;
use App\Models\Setting;
use App\Models\User;
use App\Services\Billing\PlanPricing;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class BillingTest extends TestCase
{
    use RefreshDatabase;

    private User $host;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->host = User::factory()->create(['role' => 'host', 'phone_number' => '+254712345678']);
    }

    /** @dataProvider quotes */
    public function test_quotes_follow_published_pricing(string $plan, int $units, int $devices, string $cycle, int $monthly, int $total): void
    {
        $quote = app(PlanPricing::class)->quote($plan, $units, $devices, $cycle);

        $this->assertSame($monthly, $quote['monthly_total']);
        $this->assertSame($total, $quote['total']);
    }

    public static function quotes(): array
    {
        return [
            'starter, 1 unit' => ['starter', 1, 0, 'monthly', 4500, 4500],
            'basic, 10 units: 20% off' => ['basic', 10, 0, 'monthly', 24000, 24000],
            'growth, 50 units: 30% off' => ['growth', 50, 0, 'monthly', 210000, 210000],
            'extra devices' => ['starter', 1, 2, 'monthly', 7500, 7500],
            'quarterly: 5% off' => ['starter', 1, 0, 'quarterly', 4500, 12825],
            'yearly: 2 months free' => ['starter', 1, 0, 'yearly', 4500, 45000],
        ];
    }

    public function test_billing_page_reprices_the_selection(): void
    {
        $this->actingAs($this->host)
            ->get(route('host.billing.index', ['plan' => 'basic', 'units' => 12, 'cycle' => 'quarterly']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Host/Billing')
                ->where('quote.plan', 'basic')
                ->where('quote.volume_discount', 20)
                ->where('quote.total', 82080)
                ->has('plans', 3));
    }

    public function test_mpesa_charges_the_server_quote_not_a_client_amount(): void
    {
        $this->fakeMpesa();

        $this->actingAs($this->host)->post(route('host.billing.mpesa'), [
            'plan' => 'growth', 'units' => 2, 'cycle' => 'monthly', 'phone_number' => '0712 345 678', 'amount' => 1,
        ])->assertSessionHas('success');

        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'stkpush') && $r['Amount'] === 12000 && $r['PhoneNumber'] === '254712345678');
        $transaction = MpesaTransaction::sole();
        $this->assertEquals(12000, $transaction->Amount);
        $this->assertSame('growth', $transaction->meta['plan']);
    }

    public function test_mpesa_rejects_non_safaricom_numbers(): void
    {
        $this->actingAs($this->host)->post(route('host.billing.mpesa'), [
            'plan' => 'basic', 'units' => 1, 'cycle' => 'monthly', 'phone_number' => '12345',
        ])->assertSessionHasErrors('phone_number');
    }

    public function test_confirmed_mpesa_payment_extends_the_plan_once(): void
    {
        $transaction = $this->pendingTransaction('quarterly');

        $this->postJson('/api/mpesa/callback', $this->stkCallback($transaction, 12825))->assertOk();
        $this->postJson('/api/mpesa/callback', $this->stkCallback($transaction, 12825))->assertOk();

        $this->host->refresh();
        $this->assertTrue($this->host->subscribed('default'));
        $this->assertSame('starter', $this->host->billing_plan);
        $this->assertSame('quarterly', $this->host->billing_cycle);
        $this->assertTrue($this->host->subscription('default')->ends_at->isSameDay(now()->addMonths(3)));
    }

    public function test_underpayment_does_not_extend_the_plan(): void
    {
        $transaction = $this->pendingTransaction('monthly');

        $this->postJson('/api/mpesa/callback', $this->stkCallback($transaction, 100))->assertOk();

        $this->assertFalse($this->host->fresh()->subscribed('default'));
    }

    public function test_early_renewal_extends_from_the_current_end_date(): void
    {
        $service = app(SubscriptionService::class);
        $quote = app(PlanPricing::class)->quote('basic', 1, 0, 'monthly');

        $service->activateForUser($this->host, 'mpesa', 'A', $quote['total'], $quote);
        $service->activateForUser($this->host->fresh(), 'mpesa', 'B', $quote['total'], $quote);

        $this->assertTrue($this->host->fresh()->subscription('default')->ends_at->isSameDay(now()->addMonths(2)));
        $this->assertSame(1, $this->host->subscriptions()->count());
    }

    public function test_configured_mpesa_turns_billing_on_in_auto_mode(): void
    {
        Setting::setValue('billing_enabled', 'auto', 'billing', 'string');
        config(['services.mpesa.key' => 'key', 'services.mpesa.secret' => 'secret']);

        $this->assertTrue(PlanPricing::enforced());
        $this->actingAs($this->host)->post(route('host.billing.simulate'))->assertSessionHas('error');
        $this->assertFalse($this->host->fresh()->subscribed('default'));
    }

    private function fakeMpesa(): void
    {
        config(['services.mpesa' => ['key' => 'k', 'secret' => 's', 'passkey' => 'p', 'shortcode' => '174379', 'env' => 'sandbox', 'callback_url' => 'https://x/cb']]);
        Http::fake([
            '*/oauth/*' => Http::response(['access_token' => 'tok']),
            '*/stkpush/*' => Http::response(['MerchantRequestID' => 'M1', 'CheckoutRequestID' => 'C1', 'ResponseDescription' => 'Accepted']),
        ]);
    }

    private function pendingTransaction(string $cycle): MpesaTransaction
    {
        $quote = app(PlanPricing::class)->quote('starter', 1, 0, $cycle);

        return MpesaTransaction::create([
            'user_id' => $this->host->id, 'MerchantRequestID' => 'M-'.$cycle, 'CheckoutRequestID' => 'C',
            'Amount' => $quote['total'], 'PhoneNumber' => '254712345678', 'Status' => 'pending', 'meta' => $quote,
        ]);
    }

    private function stkCallback(MpesaTransaction $transaction, int $amount): array
    {
        return ['Body' => ['stkCallback' => [
            'MerchantRequestID' => $transaction->MerchantRequestID,
            'ResultCode' => 0,
            'ResultDesc' => 'The service request is processed successfully.',
            'CallbackMetadata' => ['Item' => [
                ['Name' => 'Amount', 'Value' => $amount],
                ['Name' => 'MpesaReceiptNumber', 'Value' => 'QKX123'],
            ]],
        ]]];
    }
}
