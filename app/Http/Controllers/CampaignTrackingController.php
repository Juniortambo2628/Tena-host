<?php

namespace App\Http\Controllers;

use App\Models\CampaignRecipient;
use App\Models\Guest;
use App\Services\CampaignLinks;
use App\Support\Brand;

/**
 * Public endpoints behind campaign links and the email open pixel.
 */
class CampaignTrackingController extends Controller
{
    public function click(string $token, int $n)
    {
        $recipient = CampaignRecipient::with('campaign')->where('token', $token)->firstOrFail();
        $email = $recipient->campaign->type === 'email';
        // Emails are tracked on their rendered HTML, so resolve the link there too.
        $content = $email ? CampaignLinks::emailHtml($recipient->campaign->content ?? '') : ($recipient->campaign->content ?? '');
        $target = CampaignLinks::target($content, $n, html: $email);

        abort_unless($target, 404);
        CampaignLinks::recordClick($recipient);

        return redirect()->away($target);
    }

    /**
     * One-click unsubscribe from a campaign email (signed link). Mirrors a
     * STOP reply: the guest leaves every host's marketing for that address.
     */
    public function unsubscribe(string $token)
    {
        $recipient = CampaignRecipient::with('guest.property')->where('token', $token)->firstOrFail();
        $guest = $recipient->guest;

        if ($guest?->email) {
            Guest::where('email', $guest->email)->update(['marketing_opt_in' => false, 'opted_out_at' => now()]);
        }

        return response()->view('portal.unsubscribed', ['propertyName' => $guest?->property?->name ?? Brand::name()]);
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
