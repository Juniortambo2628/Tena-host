<?php

namespace App\Http\Controllers;

use App\Mail\PaymentReceiptMail;
use App\Models\MpesaTransaction;
use App\Models\Setting;
use App\Services\MpesaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class SubscriptionController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Host/Billing', [
            'paystackPublicKey' => config('services.paystack.public_key'),
            'subscription' => $request->user()->subscription('default'),
            'mpesaTransactions' => $request->user()->mpesaTransactions()->latest()->take(5)->get(),
        ]);
    }

    public function storePaystack(Request $request)
    {
        $request->validate([
            'reference' => 'required|string',
            'amount' => 'required|numeric|min:1',
        ]);

        $user = $request->user();

        try {
            // Verify the Paystack transaction
            $verified = $this->verifyPaystackTransaction($request->reference);

            if (! $verified) {
                return back()->with('error', 'Payment verification failed. Please try again.');
            }

            // Create or activate subscription
            if (! $user->subscribed('default')) {
                $user->subscriptions()->create([
                    'type' => 'default',
                    'stripe_id' => 'paystack_'.$request->reference,
                    'stripe_status' => 'active',
                    'stripe_price' => 'price_paystack',
                    'quantity' => 1,
                    'ends_at' => now()->addMonth(),
                ]);
            }

            // Record transaction
            $transaction = MpesaTransaction::create([
                'user_id' => $user->id,
                'MerchantRequestID' => 'paystack_'.time(),
                'CheckoutRequestID' => $request->reference,
                'Amount' => $request->amount,
                'PhoneNumber' => $user->phone_number ?? 'N/A',
                'Status' => 'completed',
                'ResultDesc' => 'Paystack payment completed',
            ]);

            // Send payment receipt email
            try {
                Mail::to($user->email)->send(new PaymentReceiptMail($transaction));
                Log::info('Paystack payment receipt email sent', ['user' => $user->email]);
            } catch (\Exception $e) {
                Log::error('Failed to send Paystack payment receipt email', ['error' => $e->getMessage()]);
            }

            return back()->with('success', 'Subscription activated successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Payment Failed: '.$e->getMessage());
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

        if ($billingEnabled === 'auto' && config('services.paystack.public_key')) {
            return back()->with('error', 'Simulation is disabled while Paystack keys are configured.');
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
        $transaction = MpesaTransaction::create([
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

        // Send payment receipt email
        try {
            Mail::to($user->email)->send(new PaymentReceiptMail($transaction));
            Log::info('Simulated payment receipt email sent', ['user' => $user->email]);
        } catch (\Exception $e) {
            Log::error('Failed to send simulated payment receipt email', ['error' => $e->getMessage()]);
        }

        return redirect()->route('host.dashboard')->with('success', 'Subscription activated (Simulated)!');
    }

    protected function verifyPaystackTransaction(string $reference): bool
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

        return $data['status'] === true && $data['data']['status'] === 'success';
    }
}
