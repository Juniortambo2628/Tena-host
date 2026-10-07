<?php

namespace App\Services\Messaging;

use App\Services\SmsDriverInterface;
use Illuminate\Support\Facades\Http;

/**
 * WhatsApp through Meta's Cloud API.
 *
 * Messages a business starts must use an approved template, so when
 * WHATSAPP_TEMPLATE is set the text is sent as that template's single body
 * variable ({{1}}). Without a template, a plain text message is sent, which
 * Meta only delivers inside the 24-hour window after the guest last wrote.
 *
 * @see https://developers.facebook.com/docs/whatsapp/cloud-api/guides/send-message-templates
 */
class WhatsAppCloudDriver implements SmsDriverInterface
{
    public function send(string $to, string $message): array
    {
        $config = config('services.whatsapp');

        if (empty($config['token']) || empty($config['phone_number_id'])) {
            return ['success' => false, 'message' => 'WhatsApp is not configured.'];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => ltrim($to, '+'),
        ];

        $payload += empty($config['template'])
            ? ['type' => 'text', 'text' => ['body' => $message]]
            : ['type' => 'template', 'template' => [
                'name' => $config['template'],
                'language' => ['code' => $config['language'] ?? 'en'],
                'components' => [[
                    'type' => 'body',
                    'parameters' => [['type' => 'text', 'text' => $message]],
                ]],
            ]];

        $response = Http::withToken($config['token'])
            ->acceptJson()
            ->timeout(15)
            ->post("https://graph.facebook.com/{$config['api_version']}/{$config['phone_number_id']}/messages", $payload);

        if ($response->successful() && $id = $response->json('messages.0.id')) {
            return ['success' => true, 'message' => $id];
        }

        return ['success' => false, 'message' => $response->json('error.message') ?? $response->body()];
    }
}
