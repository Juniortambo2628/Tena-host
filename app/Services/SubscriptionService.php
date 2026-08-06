<?php

namespace App\Services;

use App\Mail\PaymentReceiptMail;
use App\Models\MpesaTransaction;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SubscriptionService
{
    public function activateForUser(User $user, string $provider, string $reference, float $amount): MpesaTransaction
    {
        if (! $user->subscribed('default')) {
            $user->subscriptions()->create([
                'type' => 'default',
                'stripe_id' => $provider.'_'.$reference,
                'stripe_status' => 'active',
                'stripe_price' => 'price_'.$provider,
                'quantity' => 1,
                'ends_at' => now()->addMonth(),
            ]);
        }

        $transaction = MpesaTransaction::create([
            'user_id' => $user->id,
            'MerchantRequestID' => $provider.'_'.time(),
            'CheckoutRequestID' => $reference,
            'Amount' => $amount,
            'PhoneNumber' => $user->phone_number ?? 'N/A',
            'Status' => 'completed',
            'ResultDesc' => ucfirst($provider).' payment completed',
        ]);

        $this->sendReceipt($user, $transaction);

        return $transaction;
    }

    public function sendReceipt(User $user, MpesaTransaction $transaction): void
    {
        if (! $user->email) {
            return;
        }

        try {
            Mail::to($user->email)->send(new PaymentReceiptMail($transaction));
            Log::info('Payment receipt email sent', ['user' => $user->email]);
        } catch (\Exception $e) {
            Log::error('Failed to send payment receipt email', ['error' => $e->getMessage()]);
        }
    }
}
