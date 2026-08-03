<?php

namespace App\Http\Controllers;

use App\Mail\PaymentReceiptMail;
use App\Models\MpesaTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MpesaCallbackController extends Controller
{
    public function handle(Request $request)
    {
        $data = $request->all();

        Log::info('M-Pesa Callback Received', $data);

        $resultCode = $data['Body']['stkCallback']['ResultCode'] ?? null;
        $merchantRequestID = $data['Body']['stkCallback']['MerchantRequestID'] ?? null;
        $checkoutRequestID = $data['Body']['stkCallback']['CheckoutRequestID'] ?? null;
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
            $transactionDate = null;
            $amount = null;

            foreach ($callbackMetadata as $item) {
                if ($item['Name'] === 'MpesaReceiptNumber') {
                    $mpesaReceiptNumber = $item['Value'];
                }
                if ($item['Name'] === 'TransactionDate') {
                    $transactionDate = $item['Value'];
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
                $user->subscriptions()->create([
                    'type' => 'default',
                    'stripe_id' => 'mpesa_'.$mpesaReceiptNumber,
                    'stripe_status' => 'active',
                    'stripe_price' => 'price_mpesa',
                    'quantity' => 1,
                    'ends_at' => now()->addMonth(),
                ]);
            }

            // Send payment receipt email
            if ($user && $user->email) {
                try {
                    Mail::to($user->email)->send(new PaymentReceiptMail($transaction));
                    Log::info('Payment receipt email sent', ['user' => $user->email]);
                } catch (\Exception $e) {
                    Log::error('Failed to send payment receipt email', ['error' => $e->getMessage()]);
                }
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
