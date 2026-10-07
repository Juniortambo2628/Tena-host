<?php

namespace App\Http\Controllers;

use App\Models\MpesaTransaction;
use App\Services\ExtrasPaymentService;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MpesaCallbackController extends Controller
{
    /**
     * Result of a guest extras payment (ExtrasPaymentService).
     */
    public function extras(Request $request, ExtrasPaymentService $extras)
    {
        Log::info('M-Pesa extras callback received', $request->all());

        $callback = $request->input('Body.stkCallback');
        if (! is_array($callback)) {
            return response()->json(['ResultCode' => 1, 'ResultDesc' => 'Missing stkCallback']);
        }

        $extras->handleCallback($callback);

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Success']);
    }

    public function handle(Request $request, SubscriptionService $subscriptionService)
    {
        $data = $request->all();

        Log::info('M-Pesa Callback Received', $data);

        $resultCode = $data['Body']['stkCallback']['ResultCode'] ?? null;
        $merchantRequestID = $data['Body']['stkCallback']['MerchantRequestID'] ?? null;
        $resultDesc = $data['Body']['stkCallback']['ResultDesc'] ?? null;

        if (! $merchantRequestID) {
            Log::error('M-Pesa Callback: Missing MerchantRequestID', $data);

            return response()->json(['ResultCode' => 1, 'ResultDesc' => 'Missing MerchantRequestID']);
        }

        $transaction = MpesaTransaction::where('MerchantRequestID', $merchantRequestID)->first();

        if (! $transaction) {
            Log::error('M-Pesa Callback: Transaction not found', ['MerchantRequestID' => $merchantRequestID]);

            return response()->json(['ResultCode' => 1, 'ResultDesc' => 'Transaction not found']);
        }

        if ($resultCode === 0) {
            $callbackMetadata = $data['Body']['stkCallback']['CallbackMetadata']['Item'] ?? [];
            $mpesaReceiptNumber = null;
            $amount = null;

            foreach ($callbackMetadata as $item) {
                if ($item['Name'] === 'MpesaReceiptNumber') {
                    $mpesaReceiptNumber = $item['Value'];
                }
                if ($item['Name'] === 'Amount') {
                    $amount = $item['Value'];
                }
            }

            // Callbacks can repeat; only the first one extends the plan.
            if ($transaction->Status === 'completed') {
                return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Already processed']);
            }

            $transaction->update([
                'Status' => 'completed',
                'MpesaReceiptNumber' => $mpesaReceiptNumber,
                'ResultDesc' => $resultDesc,
            ]);

            $user = $transaction->user;
            if ($user && $transaction->meta && (float) $amount >= (float) $transaction->Amount) {
                $subscriptionService->extend($user, 'mpesa', (string) $mpesaReceiptNumber, $transaction->meta);
            } elseif ($user) {
                Log::warning('M-Pesa payment did not cover the quote; plan not extended', [
                    'transaction' => $transaction->id, 'paid' => $amount, 'expected' => $transaction->Amount,
                ]);
            }

            if ($user) {
                $subscriptionService->sendReceipt($user, $transaction);
            }

            Log::info('M-Pesa Payment Completed', [
                'receipt' => $mpesaReceiptNumber,
                'amount' => $amount,
            ]);
        } else {
            $transaction->update([
                'Status' => 'failed',
                'ResultDesc' => $resultDesc,
            ]);

            Log::warning('M-Pesa Payment Failed', [
                'ResultCode' => $resultCode,
                'ResultDesc' => $resultDesc,
            ]);
        }

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Success']);
    }
}
