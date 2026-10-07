<?php

namespace App\Http\Controllers;

use App\Models\CampaignRecipient;
use App\Services\CampaignLinks;

/**
 * Public endpoints behind campaign links and the email open pixel.
 */
class CampaignTrackingController extends Controller
{
    public function click(string $token, int $n)
    {
        $recipient = CampaignRecipient::with('campaign')->where('token', $token)->firstOrFail();
        $target = CampaignLinks::target($recipient->campaign->content ?? '', $n, html: $recipient->campaign->type === 'email');

        abort_unless($target, 404);
        CampaignLinks::recordClick($recipient);

        return redirect()->away($target);
    }

    public function open(string $token)
    {
        if ($recipient = CampaignRecipient::where('token', $token)->first()) {
            CampaignLinks::recordOpen($recipient);
        }

        // 1×1 transparent GIF.
        return response(base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'), 200, [
            'Content-Type' => 'image/gif',
            'Cache-Control' => 'no-store, max-age=0',
        ]);
    }
}
