<?php

namespace App\Http\Controllers;

use App\Models\MpesaTransaction;
use App\Services\Billing\PlanPricing;
use App\Services\MpesaService;
use App\Services\SubscriptionService;
use App\Support\Phone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class SubscriptionController extends Controller
{
    public function __construct(
        protected PlanPricing $pricing,
        protected SubscriptionService $subscriptions,
    ) {}

    /**
     * The billing page. Changing the plan selection reloads only `quote`
     * (with the selection as query params), so prices are always worked
     * out here.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $selection = $request->validate(PlanPricing::rules(optional: true));

        return Inertia::render('Host/Billing', [
            'paystackPublicKey' => config('services.paystack.public_key'),
            'billingLive' => PlanPricing::enforced(),
            'subscription' => $user->subscription('default'),
            'mpesaTransactions' => $user->mpesaTransactions()->latest()->take(5)->get(),
            'plans' => collect(config('billing.plans'))->map(fn ($p, $id) => $p + ['id' => $id])->values(),
            'cycles' => collect(config('billing.cycles'))->map(fn ($c, $id) => $c + ['id' => $id])->values(),
            'volumeDiscounts' => config('billing.volume_discounts'),
            'extraDevicePrice' => config('billing.extra_device_price'),
            'currentQuote' => $user->billing_plan ? $this->subscriptions->currentQuote($user) : null,
            'quote' => $this->pricing->quote(
                $selection['plan'] ?? $user->billing_plan ?? config('billing.default_plan'),
                (int) ($selection['units'] ?? $user->billing_units ?? 1),
                (int) ($selection['extra_devices'] ?? $user->billing_extra_devices ?? 0),
                $selection['cycle'] ?? $user->billing_cycle ?? 'monthly',
            ),
        ]);
    }

    public function storePaystack(Request $request)
    {
        $data = $request->validate(PlanPricing::rules() + ['reference' => 'required|string']);
        $quote = $this->quoteFor($data);

        try {
            if (! $this->verifyPaystackTransaction($data['reference'], $quote['total'])) {
                return back()->with('error', 'Payment verification failed. Please try again.');
            }

            $this->subscriptions->activateForUser($request->user(), 'paystack', $data['reference'], $quote['total'], $quote);

            return back()->with('success', 'Subscription activated successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Payment Failed: '.$e->getMessage());
        }
    }

    public function storeMpesa(Request $request, MpesaService $mpesa)
    {
        $data = $request->validate(PlanPricing::rules() + [
            'phone_number' => ['required', 'string', function ($attribute, $value, $fail) {
                if (! preg_match('/^\+2547\d{8}$|^\+2541\d{8}$/', (string) Phone::toE164($value))) {
                    $fail('Enter a Safaricom number, e.g. 0712 345 678.');
                }
            }],
        ]);
        $quote = $this->quoteFor($data);
        $phone = Phone::toE164($data['phone_number']);

        $response = $mpesa->initiateStkPush($phone, $quote['total'], 'TenaFi '.$quote['plan_name']);

        if ($response['success']) {
            MpesaTransaction::create([
                'user_id' => $request->user()->id,
                'MerchantRequestID' => $response['data']['MerchantRequestID'],
                'CheckoutRequestID' => $response['data']['CheckoutRequestID'],
                'Amount' => $quote['total'],
                'PhoneNumber' => $phone,
                'Status' => 'pending',
                'ResultDesc' => $response['data']['ResponseDescription'] ?? 'Initiated',
                'meta' => $quote,
            ]);

            return back()->with('success', 'M-Pesa request sent to your phone. Enter your PIN to pay KES '.number_format($quote['total']).'.');
        }

        return back()->with('error', $response['message'] ?? 'Failed to initiate M-Pesa payment.');
    }

    public function simulateMpesa(Request $request)
    {
        if (PlanPricing::enforced()) {
            return back()->with('error', 'Simulation is disabled while billing is live.');
        }

        $quote = $request->filled('plan')
            ? $this->quoteFor($request->validate(PlanPricing::rules()))
            : $this->subscriptions->currentQuote($request->user());

        $this->subscriptions->activateForUser($request->user(), 'mpesa', 'SIM_'.time(), $quote['total'], $quote);

        return redirect()->route('host.dashboard')->with('success', 'Subscription activated (Simulated)!');
    }

    /**
     * @param  array<string, mixed>  $data  validated plan selection
     * @return array<string, mixed>
     */
    protected function quoteFor(array $data): array
    {
        return $this->pricing->quote($data['plan'], (int) $data['units'], (int) ($data['extra_devices'] ?? 0), $data['cycle']);
    }

    /**
     * Confirm with Paystack that this reference paid the quoted KES amount.
     */
    protected function verifyPaystackTransaction(string $reference, int $expectedKes): bool
    {
        $secret = config('services.paystack.secret');

        if (! $secret) {
            return false;
        }

        $ch = curl_init("https://api.paystack.co/transaction/verify/{$reference}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$secret}",
                'Cache-Control: no-cache',
            ],
        ]);

        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            Log::error('Paystack verification error', ['error' => $err]);

            return false;
        }

        $data = json_decode($response, true);

        return ($data['status'] ?? false) === true
            && ($data['data']['status'] ?? null) === 'success'
            && ($data['data']['currency'] ?? null) === config('billing.currency')
            && (int) ($data['data']['amount'] ?? 0) >= $expectedKes * 100;
    }
}
