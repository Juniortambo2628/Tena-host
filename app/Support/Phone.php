<?php

namespace App\Support;

/**
 * Phone numbers are stored in E.164. Kenyan numbers arrive in many shapes
 * ("0712 345 678", "712345678", "254712345678", "+254 712 345 678").
 */
class Phone
{
    public static function toE164(?string $phone, string $countryCode = '254'): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);

        return match (true) {
            str_starts_with(trim($phone), '+') => '+'.$digits,
            str_starts_with($digits, $countryCode) => '+'.$digits,
            default => '+'.$countryCode.ltrim($digits, '0'),
        };
    }

    /** True for a plausible E.164 number (+ and 10-15 digits). */
    public static function isValid(?string $e164): bool
    {
        return (bool) preg_match('/^\+\d{10,15}$/', (string) $e164);
    }
}
