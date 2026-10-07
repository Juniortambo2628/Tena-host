<?php

namespace App\Services\Messaging;

use App\Models\Guest;
use App\Support\Brand;
use App\Support\Phone;

/**
 * Replies to campaigns: STOP opts a phone number out of marketing at every
 * property, START opts it back in. Used by the WhatsApp webhook and the
 * Africa's Talking inbound SMS callback.
 */
class OptOutService
{
    public const STOP_WORDS = ['stop', 'unsubscribe', 'stopall', 'cancel', 'quit', 'end', 'acha'];

    public const START_WORDS = ['start', 'subscribe', 'unstop'];

    public const FOOTER = 'Reply STOP to opt out.';

    public function __construct(protected Messenger $messenger) {}

    /**
     * Handle an inbound message. Returns 'stopped', 'started' or null.
     */
    public function handle(string $channel, string $from, string $text): ?string
    {
        $word = strtolower(trim(preg_replace('/[^\p{L}]/u', '', $text)));
        $phone = Phone::toE164($from);

        if (! $phone || ! Phone::isValid($phone)) {
            return null;
        }

        if (in_array($word, self::STOP_WORDS, true)) {
            Guest::where('phone', $phone)->update(['marketing_opt_in' => false, 'opted_out_at' => now()]);
            $this->messenger->send($channel, $phone, "You won't get offers via ".Brand::name().' any more. Reply START to opt back in.');

            return 'stopped';
        }

        if (in_array($word, self::START_WORDS, true)) {
            Guest::where('phone', $phone)->whereNotNull('opted_out_at')->update(['marketing_opt_in' => true, 'opted_out_at' => null]);
            $this->messenger->send($channel, $phone, "You're opted back in to offers via ".Brand::name().'. Reply STOP any time.');

            return 'started';
        }

        return null;
    }

    /**
     * Campaign text with the opt-out line (WhatsApp and SMS).
     */
    public static function withFooter(string $content): string
    {
        return str_contains(strtolower($content), 'reply stop') ? $content : rtrim($content)."\n\n".self::FOOTER;
    }
}
