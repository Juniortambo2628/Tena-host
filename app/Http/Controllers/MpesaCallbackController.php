<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\MpesaTransaction;
use App\Services\ExtrasPaymentService;
use App\Services\MpesaService;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Safaricom's STK push results. Every prompt is sent with its own token in the
 * CallBackURL; a callback is applied only when that token and the prompt's
 * CheckoutRequestID both match. Anything else gets Daraja's "accepted" answer
 * and changes nothing. Callback bodies hold the payer's phone number, so only
 * the result code and our own id are logged.
 */
class MpesaCallbackController extends Controller
{
    /**
     * Result of a guest extras payment (ExtrasPaymentService).
     */
    public function extras(Request $request, ExtrasPaymentService $extras, ?string $token = null): JsonResponse
    {
        $callback = $request->input('Body.stkCallback');

        if (is_array($callback)) {
            $extras->handleCallback($callback, $token);
        } else {
            Log::warning('M-Pesa extras callback ignored', ['reason' => 'malformed']);
        }

        return self::accepted();
    }

    /**
     * Result of a subscription or pay-per-event payment.
     */
    public function handle(Request $request, SubscriptionService $subscriptionService, ?string $token = null): JsonResponse
    {
        $callback = $request->input('Body.stkCallback');
        $resultCode = is_array($callback) && isset($callback['ResultCode']) ? (int) $callback['ResultCode'] : null;
        $transaction = is_array($callback) ? $this->findTransaction($callback, $token) : null;

        if (! $transaction) {
            Log::warning('M-Pesa callback ignored', ['result_code' => $resultCode]);

            return self::accepted();
        }

        $outcome = DB::transaction(function () use ($transaction, $callback, $resultCode, $subscriptionService) {
            // Two copies of the same callback can arrive together: the row lock
            // makes the second wait, and then it finds the payment settled.
            $locked = MpesaTransaction::whereKey($transaction->getKey())->lockForUpdate()->first();
            if (! $locked || $locked->Status !== 'pending') {
                return 'duplicate';
            }

            $resultDesc = (string) ($callback['ResultDesc'] ?? '');
            if ($resultCode !== 0) {
                $locked->update(['Status' => 'failed', 'ResultDesc' => $resultDesc]);

                return 'failed';
            }

            $items = MpesaService::callbackItems($callback);
            $receipt = isset($items['MpesaReceiptNumber']) ? (string) $items['MpesaReceiptNumber'] : null;

            if (! MpesaService::amountMatches($items['Amount'] ?? null, $locked->Amount)) {
                // Keep the receipt so the payment can be found and refunded.
                $locked->update(['Status' => 'failed', 'MpesaReceiptNumber' => $receipt, 'ResultDesc' => 'Paid amount did not match the prompt']);

                return 'amount_mismatch';
            }

            $locked->update(['Status' => 'completed', 'MpesaReceiptNumber' => $receipt, 'ResultDesc' => $resultDesc]);

            if (($locked->meta['type'] ?? null) === 'event') {
                // Pay per event: the payment switches on that event's WiFi page; no plan is extended.
                Event::find($locked->meta['event_id'] ?? null)?->markPaid();
            } elseif ($locked->user && $locked->meta) {
                $subscriptionService->extend($locked->user, 'mpesa', (string) $receipt, $locked->meta);
            }

            return 'completed';
        });

        Log::info('M-Pesa callback', ['result_code' => $resultCode, 'transaction' => $transaction->id, 'outcome' => $outcome]);

        if ($outcome === 'completed' && ($user = $transaction->user)) {
            $subscriptionService->sendReceipt($user, $transaction->fresh());
        }

        return self::accepted();
    }

    /**
     * The pending prompt this callback answers, or null.
     *
     * @param  array<string, mixed>  $callback
     */
    private function findTransaction(array $callback, ?string $token): ?MpesaTransaction
    {
        $checkoutId = (string) ($callback['CheckoutRequestID'] ?? '');
        if ($checkoutId === '') {
            return null;
        }

        if ($token !== null) {
            $transaction = MpesaTransaction::where('callback_token', $token)->first();

            return $transaction
                && MpesaService::tokenMatches($transaction->callback_token, $token)
                && hash_equals((string) $transaction->CheckoutRequestID, $checkoutId)
                ? $transaction : null;
        }

        // The old token-less URL: only prompts sent before tokens existed, for a short while.
        return MpesaTransaction::whereNull('callback_token')
            ->where('Status', 'pending')
            ->where('CheckoutRequestID', $checkoutId)
            ->where('MerchantRequestID', (string) ($callback['MerchantRequestID'] ?? ''))
            ->where('created_at', '>=', now()->subMinutes(MpesaService::LEGACY_CALLBACK_MINUTES))
            ->first();
    }

    /** Daraja's "received" answer, whatever happened, so Safaricom stops retrying. */
    private static function accepted(): JsonResponse
    {
        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }
}
