<?php

namespace App\Http\Controllers;

use App\Models\MpesaTransaction;
use App\Models\Setting;
use App\Services\MpesaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class SubscriptionController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Host/Billing', [
            'stripeKey' => config('cashier.key'),
            'subscription' => $request->user()->subscription('default'),
            'mpesaTransactions' => $request->user()->mpesaTransactions()->latest()->take(5)->get(),
        ]);
    }

    public function storeStripe(Request $request)
    {
        $request->validate([
            'payment_method' => 'required',
            'plan_id' => 'required', // price_xxxx
        ]);

        $user = $request->user();

        try {
            $user->newSubscription('default', $request->plan_id)
                ->create($request->payment_method);

            return back()->with('success', 'Subscription activated successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Stripe Payment Failed: '.$e->getMessage());
        }
    }

    public function storeMpesa(Request $request, MpesaService $mpesa)
    {
        $request->validate([
            'phone_number' => 'required|regex:/^(07|01)[0-9]{8}$/', // basic KE regex
            'amount' => 'required|numeric|min:1',
        ]);

        $response = $mpesa->initiateStkPush(
            $request->phone_number,
            $request->amount,
            'Tena Subscription'
        );

        if ($response['success']) {
            // Log the transaction as pending
            MpesaTransaction::create([
                'user_id' => $request->user()->id,
                'MerchantRequestID' => $response['data']['MerchantRequestID'],
                'CheckoutRequestID' => $response['data']['CheckoutRequestID'],
                'Amount' => $request->amount,
                'PhoneNumber' => $request->phone_number,
                'Status' => 'pending',
                'ResultDesc' => $response['data']['ResponseDescription'] ?? 'Initiated',
            ]);

            return back()->with('success', 'M-Pesa STK Push sent to your phone. Please enter your PIN.');
        }

        return back()->with('error', $response['message'] ?? 'Failed to initiate M-Pesa payment.');
    }

    public function simulateMpesa(Request $request)
    {
        $billingEnabled = Setting::getValue('billing_enabled', 'auto');

        if ($billingEnabled === 'enabled') {
            return back()->with('error', 'Simulation is disabled when billing is explicitly enabled.');
        }

        if ($billingEnabled === 'auto' && config('services.stripe.key')) {
            return back()->with('error', 'Simulation is disabled while Stripe keys are configured.');
        }

        $user = $request->user();

        // Mock default subscription
        if (! $user->subscribed('default')) {
            $user->subscriptions()->create([
                'type' => 'default',
                'stripe_id' => 'mpesa_'.time(),
                'stripe_status' => 'active',
                'stripe_price' => 'price_mpesa_mock',
                'quantity' => 1,
                'trial_ends_at' => null,
                'ends_at' => now()->addMonth(),
            ]);
        }

        // Record transaction
        MpesaTransaction::create([
            'MerchantRequestID' => 'SIM_'.time(),
            'CheckoutRequestID' => 'SIM_'.time(),
            'ResultCode' => 0,
            'ResultDesc' => 'Simulated Success',
            'Amount' => 6500,
            'MpesaReceiptNumber' => 'SIM'.time(),
            'TransactionDate' => now(),
            'PhoneNumber' => $user->phone_number ?? '0700000000',
            'user_id' => $user->id,
            'Status' => 'completed',
        ]);

        return redirect()->route('host.dashboard')->with('success', 'Subscription activated (Simulated)!');
    }
}
