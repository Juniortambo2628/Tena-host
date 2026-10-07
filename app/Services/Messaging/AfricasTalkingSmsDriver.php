<?php

namespace App\Services\Messaging;

use App\Services\SmsDriverInterface;
use Illuminate\Support\Facades\Http;

/**
 * SMS through Africa's Talking (Kenya's default SMS gateway).
 *
 * @see https://developers.africastalking.com/docs/sms/sending/bulk
 */
class AfricasTalkingSmsDriver implements SmsDriverInterface
{
    public function send(string $to, string $message): array
    {
        $config = config('services.africastalking');

        if (empty($config['username']) || empty($config['api_key'])) {
            return ['success' => false, 'message' => "Africa's Talking is not configured."];
        }

        $host = $config['username'] === 'sandbox' ? 'api.sandbox.africastalking.com' : 'api.africastalking.com';

        $response = Http::asForm()
            ->acceptJson()
            ->withHeaders(['apiKey' => $config['api_key']])
            ->timeout(15)
            ->post("https://{$host}/version1/messaging", array_filter([
                'username' => $config['username'],
                'to' => $to,
                'message' => $message,
                'from' => $config['from'] ?? null,
            ]));

        $recipient = $response->json('SMSMessageData.Recipients.0');

        // 100 Processed, 101 Sent, 102 Queued.
        if ($response->successful() && in_array($recipient['statusCode'] ?? null, [100, 101, 102], true)) {
            return ['success' => true, 'message' => $recipient['messageId'] ?? 'sent'];
        }

        return ['success' => false, 'message' => $recipient['status'] ?? $response->json('SMSMessageData.Message') ?? $response->body()];
    }
}
