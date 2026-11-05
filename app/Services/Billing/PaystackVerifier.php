<?php

namespace App\Services\Billing;

use Illuminate\Support\Facades\Log;

/**
 * Confirms with Paystack that a card payment reference paid at least the
 * quoted amount, in KES. Used for plans and for event quotes.
 */
class PaystackVerifier
{
    public function verify(string $reference, int $expectedKes): bool
    {
        $secret = config('services.paystack.secret');

        if (! $secret) {
            return false;
        }

        $ch = curl_init('https://api.paystack.co/transaction/verify/'.rawurlencode($reference));
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
