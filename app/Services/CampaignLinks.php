<?php

namespace App\Services;

use App\Models\CampaignRecipient;
use App\Models\MarketingEvent;

/**
 * Open and click tracking for campaigns. Each link in a message becomes a
 * short per-recipient link (/c/{token}/{n}); email adds an open pixel.
 * The first open or click per recipient is counted on the campaign.
 */
class CampaignLinks
{
    /** Links in plain text (WhatsApp, SMS). */
    private const TEXT_URL = '~https?://[^\s<>"\']+~i';

    /** Links in email HTML: href targets only, so images keep loading. */
    private const HREF_URL = '~(?<=href=["\'])https?://[^"\']+~i';

    /**
     * A campaign email's body as HTML. The studio writes plain text: it is
     * escaped, links become anchors and line breaks are kept. Content that is
     * already HTML (older campaigns) passes through unchanged.
     */
    public static function emailHtml(string $content): string
    {
        // Real markup only: "<3" or "a < b" in a plain message must still be escaped.
        if (preg_match('~</?(p|br|a|div|span|strong|b|em|i|ul|ol|li|h[1-6]|img|table)\b[^<>]*>~i', $content)) {
            return $content;
        }

        $html = preg_replace_callback('~https?://[^\s<>"\']+~i', function ($m) {
            $url = rtrim($m[0], '.,;:!?)');

            return '<a href="'.$url.'">'.$url.'</a>'.substr($m[0], strlen($url));
        }, e($content));

        return nl2br($html, false);
    }

    /**
     * Swap every link for the recipient's tracked link.
     */
    public static function track(string $content, CampaignRecipient $recipient, bool $html = false): string
    {
        $n = 0;

        return preg_replace_callback($html ? self::HREF_URL : self::TEXT_URL, function () use (&$n, $recipient) {
            return route('campaigns.click', ['token' => $recipient->token, 'n' => $n++]);
        }, $content);
    }

    /**
     * The original link number $n in the campaign's content.
     */
    public static function target(string $content, int $n, bool $html = false): ?string
    {
        preg_match_all($html ? self::HREF_URL : self::TEXT_URL, $content, $matches);

        return html_entity_decode($matches[0][$n] ?? '') ?: null;
    }

    public static function pixel(CampaignRecipient $recipient): string
    {
        return route('campaigns.open', $recipient->token);
    }

    public static function recordClick(CampaignRecipient $recipient): void
    {
        // A click also proves the message was opened.
        static::recordOpen($recipient);
        static::recordOnce($recipient, 'clicked_at', 'clicked', 'total_clicked');
    }

    public static function recordOpen(CampaignRecipient $recipient): void
    {
        static::recordOnce($recipient, 'opened_at', 'opened', 'total_opened');
    }

    private static function recordOnce(CampaignRecipient $recipient, string $column, string $event, string $counter): void
    {
        // Atomic: only the request that flips the column counts it.
        $updated = CampaignRecipient::whereKey($recipient->id)->whereNull($column)->update([$column => now()]);

        if ($updated) {
            MarketingEvent::create(['campaign_id' => $recipient->campaign_id, 'guest_id' => $recipient->guest_id, 'event_type' => $event]);
            $recipient->campaign()->increment($counter);
        }
    }
}
