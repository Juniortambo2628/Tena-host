<?php

namespace App\Http\Controllers;

use App\Models\MpesaTransaction;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MpesaCallbackController extends Controller
{
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

            $transaction->update([
                'Status' => 'completed',
                'MpesaReceiptNumber' => $mpesaReceiptNumber,
                'ResultDesc' => $resultDesc,
            ]);

            $user = $transaction->user;
            if ($user && ! $user->subscribed('default')) {
                $subscriptionService->activateForUser($user, 'mpesa', $mpesaReceiptNumber, $amount ?? 0);
            } else {
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
