<?php

namespace App\Http\Controllers;

use App\Models\CampaignRecipient;
use App\Services\CampaignLinks;
use App\Services\Messaging\OptOutService;
use Illuminate\Http\Request;

/**
 * Inbound messaging: WhatsApp Cloud API webhook (replies + read receipts)
 * and Africa's Talking incoming SMS.
 */
class MessagingWebhookController extends Controller
{
    /**
     * Meta's one-time subscription check.
     */
    public function verifyWhatsApp(Request $request)
    {
        $token = config('services.whatsapp.verify_token');

        abort_unless($token && $request->query('hub_mode') === 'subscribe' && hash_equals($token, (string) $request->query('hub_verify_token')), 403);

        return response((string) $request->query('hub_challenge'));
    }

    public function whatsApp(Request $request, OptOutService $optOut)
    {
        // Meta signs each delivery with the app secret.
        if ($secret = config('services.whatsapp.app_secret')) {
            $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $secret);
            abort_unless(hash_equals($expected, (string) $request->header('X-Hub-Signature-256')), 403);
        }

        foreach ($request->input('entry', []) as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $value = $change['value'] ?? [];

                foreach ($value['messages'] ?? [] as $message) {
                    $text = $message['text']['body'] ?? $message['button']['text'] ?? '';
                    $optOut->handle('whatsapp', '+'.($message['from'] ?? ''), $text);
                }

                foreach ($value['statuses'] ?? [] as $status) {
                    if (($status['status'] ?? null) === 'read'
                        && $recipient = CampaignRecipient::where('message_id', $status['id'] ?? '')->first()) {
                        CampaignLinks::recordOpen($recipient);
                    }
                }
            }
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Africa's Talking incoming SMS (form post: from, text).
     */
    public function sms(Request $request, OptOutService $optOut)
    {
        $optOut->handle('sms', (string) $request->input('from'), (string) $request->input('text'));

        return response('OK');
    }
}
