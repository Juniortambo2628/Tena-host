<?php

namespace App\Services;

use App\Mail\PaymentReceiptMail;
use App\Models\MpesaTransaction;
use App\Models\User;
use App\Services\Billing\PlanPricing;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SubscriptionService
{
    public function __construct(protected PlanPricing $pricing) {}

    /**
     * The quote for the user's saved plan selection.
     *
     * @return array<string, mixed>
     */
    public function currentQuote(User $user): array
    {
        return $this->pricing->quote(
            $user->billing_plan ?: config('billing.default_plan'),
            $user->billing_units ?: 1,
            $user->billing_extra_devices ?: 0,
            $user->billing_cycle ?: 'monthly',
        );
    }

    /**
     * Record a confirmed payment (card, simulation) and extend the plan.
     *
     * @param  array<string, mixed>|null  $quote  what was paid for; defaults to the user's current selection
     */
    public function activateForUser(User $user, string $provider, string $reference, float $amount, ?array $quote = null): MpesaTransaction
    {
        $quote ??= $this->currentQuote($user);

        $transaction = MpesaTransaction::create([
            'user_id' => $user->id,
            'MerchantRequestID' => $provider.'_'.time(),
            'CheckoutRequestID' => $reference,
            'Amount' => $amount,
            'PhoneNumber' => $user->phone_number ?? 'N/A',
            'Status' => 'completed',
            'ResultDesc' => ucfirst($provider).' payment completed',
            'meta' => $quote,
        ]);

        $this->extend($user, $provider, $reference, $quote);
        $this->sendReceipt($user, $transaction);

        return $transaction;
    }

    /**
     * Apply a paid quote: save the plan on the user and push the paid-through
     * date forward by the cycle's months (from today, or from the current end
     * date when renewing early).
     *
     * @param  array<string, mixed>  $quote
     */
    public function extend(User $user, string $provider, string $reference, array $quote): void
    {
        $user->forceFill([
            'billing_plan' => $quote['plan'],
            'billing_units' => $quote['units'],
            'billing_extra_devices' => $quote['extra_devices'],
            'billing_cycle' => $quote['cycle'],
        ])->save();

        $subscription = $user->subscriptions()->where('type', 'default')->first();
        $from = $subscription?->ends_at?->isFuture() ? $subscription->ends_at : now();
        $attributes = [
            'stripe_id' => $provider.'_'.$reference,
            'stripe_status' => 'active',
            'stripe_price' => $quote['plan'].'_'.$quote['cycle'],
            'quantity' => $quote['units'],
            'ends_at' => $from->copy()->addMonths($quote['months']),
        ];

        $subscription
            ? $subscription->update($attributes)
            : $user->subscriptions()->create(['type' => 'default'] + $attributes);
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
