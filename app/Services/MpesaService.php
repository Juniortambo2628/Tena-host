<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MpesaService
{
    protected $consumerKey;

    protected $consumerSecret;

    protected $passKey;

    protected $shortCode;

    protected $env; // sandbox or production

    protected $baseUrl;

    public function __construct()
    {
        $this->consumerKey = config('services.mpesa.key');
        $this->consumerSecret = config('services.mpesa.secret');
        $this->passKey = config('services.mpesa.passkey');
        $this->shortCode = config('services.mpesa.shortcode');
        $this->env = config('services.mpesa.env', 'sandbox');

        $this->baseUrl = $this->env === 'production'
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';
    }

    public function getAccessToken()
    {
        $url = $this->baseUrl.'/oauth/v1/generate?grant_type=client_credentials';

        $response = Http::withBasicAuth($this->consumerKey, $this->consumerSecret)
            ->get($url);

        if ($response->successful()) {
            return $response->json()['access_token'];
        }

        Log::error('M-Pesa Auth Failed: '.$response->body());

        return null;
    }

    public function initiateStkPush($phoneNumber, $amount, $reference = 'Subscription')
    {
        $token = $this->getAccessToken();
        if (! $token) {
            return ['success' => false, 'message' => 'Failed to authenticate with M-Pesa'];
        }

        $url = $this->baseUrl.'/mpesa/stkpush/v1/processrequest';
        $timestamp = date('YmdHis');
        $password = base64_encode($this->shortCode.$this->passKey.$timestamp);

        // Ensure 254 format
        if (str_starts_with($phoneNumber, '0')) {
            $phoneNumber = '254'.substr($phoneNumber, 1);
        } elseif (str_starts_with($phoneNumber, '+254')) {
            $phoneNumber = substr($phoneNumber, 1);
        }

        $payload = [
            'BusinessShortCode' => $this->shortCode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => (int) $amount, // Ensure integer
            'PartyA' => $phoneNumber,
            'PartyB' => $this->shortCode,
            'PhoneNumber' => $phoneNumber,
            'CallBackURL' => config('services.mpesa.callback_url') ?? 'https://example.com/api/mpesa/callback', // Sandbox needs a valid URL or ngrok
            'AccountReference' => $reference,
            'TransactionDesc' => 'Host Subscription Payment',
        ];

        $response = Http::withToken($token)->post($url, $payload);

        if ($response->successful()) {
            return [
                'success' => true,
                'data' => $response->json(),
            ];
        }

        Log::error('M-Pesa STK Push Failed: '.$response->body());

        return [
            'success' => false,
            'message' => 'STK Push failed: '.($response->json()['errorMessage'] ?? 'Unknown Error'),
            'raw' => $response->json(),
        ];
    }
}
