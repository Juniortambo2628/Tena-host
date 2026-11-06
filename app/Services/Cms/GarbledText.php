<?php

namespace App\Services\Cms;

/**
 * Repairs CMS text saved by the old cPanel server (up to late October 2026),
 * whose database connection turned every byte of a non-ASCII character into
 * "?": isn’t became "isn???t", cafés became "caf??s".
 *
 * A text still as the page blueprint wrote it is restored from the blueprint
 * exactly. Text an admin edited is mended from context: a curly apostrophe
 * between letters, curly quotes around words, é inside a word. Anything else
 * is left for a person (unrepaired()).
 */
class GarbledText
{
    public static function isGarbled(?string $value): bool
    {
        return $value !== null && preg_match('/\?{2,4}/', $value) === 1;
    }

    /** The blueprint value, if this is it with its special characters lost. */
    public static function fromBlueprint(string $value, ?string $blueprint): ?string
    {
        if ($blueprint === null || $blueprint === $value) {
            return null;
        }
        $lossy = preg_replace_callback('/[^\x00-\x7F]/u', fn ($m) => str_repeat('?', strlen($m[0])), $blueprint);

        return $lossy === $value ? $blueprint : null;
    }

    /** Mend from context; runs that can't be told apart stay as they are. */
    public static function mend(string $value): string
    {
        $space = '(?:\s|&nbsp;)';
        $rules = [
            // ’ in a contraction or possessive: isn’t, guest’s, you’ll, we’re, I’m.
            '/(?<=[A-Za-z])\?{3}(?=(?:s|t|ll|re|ve|d|m)\b)/' => "\u{2019}",
            // — between two words with no spaces: commissions—it’s.
            '/(?<=[A-Za-z])\?{3}(?=[A-Za-z])/' => "\u{2014}",
            // — standing alone between spaces: revenue — and.
            '/(?<='.'\s|;)\?{3}(?='.$space.')/' => "\u{2014}",
            // “ opening a quote, ” closing one.
            '/(?:^|(?<=[\s>(;\[\/"]))\?{3}(?=[A-Za-z0-9£$])/' => "\u{201C}",
            '/(?<=[A-Za-z0-9.!?,)])\?{3}(?=$|[\s<&.,;:!?)\]"])/' => "\u{201D}",
            // é ending or inside a word: Café, cafés.
            '/(?<=[A-Za-z])\?{2}(?=$|[A-Za-z"\'\s,.;:)])/' => "\u{00E9}",
            // A non-breaking space after a full stop, and · as a separator between spaces.
            '/(?<=[.!?:])\?{2}(?='.$space.'|$)/' => '',
            '/(?<=\s)\?{2}(?=\s)/' => "\u{00B7}",
        ];

        return preg_replace(array_keys($rules), array_values($rules), $value);
    }

    /** Runs of question marks mend() could not place. */
    public static function unrepaired(string $value): array
    {
        preg_match_all('/.{0,20}\?{2,4}.{0,20}/u', $value, $m);

        return $m[0];
    }
}
