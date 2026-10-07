<?php

namespace Tests\Feature;

use App\Models\Amenity;
use App\Models\Guest;
use App\Models\Order;
use App\Models\Property;
use App\Models\Setting;
use App\Models\User;
use App\Services\MpesaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MpesaExtrasTest extends TestCase
{
    use RefreshDatabase;

    private User $guestUser;

    private Property $property;

    private Amenity $lateCheckout;

    protected function setUp(): void
    {
        parent::setUp();

        $host = User::factory()->create(['role' => 'host', 'first_name' => 'Amina', 'phone_number' => '+254700000001']);
        $this->property = Property::factory()->create(['user_id' => $host->id, 'name' => 'Sunset Villa']);
        $this->lateCheckout = Amenity::factory()->create(['property_id' => $this->property->id, 'name' => 'Late checkout', 'price' => 1500, 'is_active' => true]);
        $this->guestUser = User::factory()->create(['role' => 'guest']);
        Guest::factory()->create(['property_id' => $this->property->id, 'user_id' => $this->guestUser->id, 'first_name' => 'Wanjiru', 'phone' => '+254712345678']);
    }

    private function configureMpesa(array $overrides = []): void
    {
        $settings = array_merge([
            'mpesa_env' => 'sandbox', 'mpesa_account_type' => 'paybill', 'mpesa_shortcode' => '600100',
            'mpesa_consumer_key' => 'ck', 'mpesa_consumer_secret' => Crypt::encryptString('cs'),
            'mpesa_passkey' => Crypt::encryptString('pk'), 'mpesa_extras_fee_percent' => '10',
        ], $overrides);
        foreach ($settings as $key => $value) {
            Setting::setValue($key, $value, 'mpesa');
        }

        config(['services.whatsapp' => [
            'driver' => 'whatsapp_cloud', 'token' => 't', 'phone_number_id' => '1', 'template' => null,
            'language' => 'en', 'api_version' => 'v21.0', 'fallback_to_sms' => false,
        ]]);
        Http::fake([
            '*/oauth/*' => Http::response(['access_token' => 'tok']),
            '*/stkpush/*' => Http::response(['MerchantRequestID' => 'M1', 'CheckoutRequestID' => 'ws_CO_1', 'ResponseDescription' => 'Accepted']),
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.1']]]),
        ]);
    }

    private function order(array $data = [])
    {
        return $this->actingAs($this->guestUser)->post(route('guest.orders.store'), ['amenity_id' => $this->lateCheckout->id] + $data);
    }

    private function stkCallback(int $amount, int $resultCode = 0): array
    {
        return ['Body' => ['stkCallback' => [
            'MerchantRequestID' => 'M1', 'CheckoutRequestID' => 'ws_CO_1', 'ResultCode' => $resultCode, 'ResultDesc' => 'ok',
            'CallbackMetadata' => ['Item' => [['Name' => 'Amount', 'Value' => $amount], ['Name' => 'MpesaReceiptNumber', 'Value' => 'QKX9']]],
        ]]];
    }

    public function test_placeholders_alone_mean_pay_at_the_property(): void
    {
        $this->assertSame(MpesaService::SANDBOX_SHORTCODE, MpesaService::settings()['shortcode']);
        $this->assertFalse(MpesaService::fromSettings()->isConfigured());

        $this->order()->assertSessionHas('success', 'Order placed. Please pay your host directly.');

        $this->assertSame('unpaid', Order::sole()->payment_status);
    }

    public function test_priced_extra_sends_an_stk_push_to_tenafis_paybill(): void
    {
        $this->configureMpesa();

        $this->order(['phone' => '0712 345 678'])->assertSessionHas('success');

        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'stkpush')
            && $r['BusinessShortCode'] === '600100' && $r['TransactionType'] === 'CustomerPayBillOnline'
            && $r['Amount'] === 1500 && $r['PhoneNumber'] === '254712345678'
            && $r['AccountReference'] === 'TF'.Order::sole()->id
            && $r['CallBackURL'] === route('mpesa.extras.callback'));
        $this->assertSame(['pending', 'ws_CO_1'], [Order::sole()->payment_status, Order::sole()->mpesa_checkout_request_id]);
    }

    public function test_till_numbers_use_buy_goods(): void
    {
        $this->configureMpesa(['mpesa_account_type' => 'till']);

        $this->order();

        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'stkpush') && $r['TransactionType'] === 'CustomerBuyGoodsOnline');
    }

    public function test_confirmed_payment_marks_paid_records_fee_and_tells_the_host_once(): void
    {
        $this->configureMpesa();
        $this->order();

        $this->postJson(route('mpesa.extras.callback'), $this->stkCallback(1500))->assertJson(['ResultCode' => 0]);
        $this->postJson(route('mpesa.extras.callback'), $this->stkCallback(1500));

        $order = Order::sole();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('QKX9', $order->mpesa_receipt);
        $this->assertEquals(150, $order->platform_fee);
        $this->assertEquals(1350, $order->host_payout);
        Http::assertSentCount(3); // token + STK push + one host WhatsApp
        Http::assertSent(fn (Request $r) => str_contains((string) ($r['text']['body'] ?? ''), 'Wanjiru paid KES 1,500 for Late checkout at Sunset Villa (M-Pesa QKX9)'));
    }

    public function test_cancelled_or_short_payments_fail(): void
    {
        $this->configureMpesa();
        $this->order();
        $this->postJson(route('mpesa.extras.callback'), $this->stkCallback(1500, resultCode: 1032));
        $this->assertSame('failed', Order::sole()->payment_status);

        Order::sole()->update(['payment_status' => 'pending']);
        $this->postJson(route('mpesa.extras.callback'), $this->stkCallback(100));
        $this->assertSame('failed', Order::sole()->payment_status);
    }

    public function test_free_extras_need_no_payment(): void
    {
        $this->configureMpesa();
        $this->lateCheckout->update(['price' => 0]);

        $this->order();

        $this->assertSame('not_required', Order::sole()->payment_status);
        Http::assertNothingSent();
    }

    public function test_admin_configures_the_paybill_and_secrets_stay_encrypted(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.mpesa.update'), [
            'env' => 'production', 'account_type' => 'paybill', 'shortcode' => '4123456',
            'consumer_key' => 'live-key', 'consumer_secret' => 'live-secret', 'passkey' => 'live-pass',
            'extras_enabled' => true, 'extras_fee_percent' => 5,
        ])->assertSessionHasNoErrors();

        $this->assertNotSame('live-secret', Setting::getValue('mpesa_consumer_secret'));
        $settings = MpesaService::settings();
        $this->assertSame(['production', '4123456', 'live-key', 'live-secret', 'live-pass'],
            [$settings['env'], $settings['shortcode'], $settings['key'], $settings['secret'], $settings['passkey']]);
        $this->assertTrue(MpesaService::fromSettings()->isConfigured());

        $this->actingAs($admin)->get(route('admin.mpesa.edit'))->assertInertia(fn ($page) => $page
            ->where('settings.shortcode', '4123456')
            ->where('settings.has_secret', true)
            ->missing('settings.consumer_secret')
            ->where('configured', true));
    }

    public function test_admin_sees_and_settles_host_payouts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $guest = Guest::first();
        Order::create(['guest_id' => $guest->id, 'property_id' => $this->property->id, 'amenity_id' => $this->lateCheckout->id,
            'status' => 'pending', 'total' => 1500, 'payment_status' => 'paid', 'platform_fee' => 150, 'paid_at' => now()]);
        Order::create(['guest_id' => $guest->id, 'property_id' => $this->property->id, 'amenity_id' => $this->lateCheckout->id,
            'status' => 'pending', 'total' => 1500, 'payment_status' => 'unpaid']);

        $this->actingAs($admin)->get(route('admin.mpesa.edit'))->assertInertia(fn ($page) => $page
            ->has('payouts', 1)
            ->where('payouts.0.orders', 1)
            ->where('payouts.0.owed', 1350));

        $this->actingAs($admin)->post(route('admin.mpesa.settle', $this->property->user_id))->assertSessionHas('success');

        $this->assertNotNull(Order::where('payment_status', 'paid')->sole()->settled_at);
    }
}
