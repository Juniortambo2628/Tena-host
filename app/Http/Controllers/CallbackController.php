<?php

namespace App\Http\Controllers;

use App\Models\LandingPage;
use App\Models\Registration;
use App\Models\Setting;
use App\Services\SignupAlertService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * The homepage hero's "Get a call back" form: a Kenyan mobile number and
 * what the visitor runs. Stored as a sign-up lead (source_page = callback)
 * and announced the same way as new sign-ups. Off until switched on in
 * Admin -> Settings ("callbacks_enabled").
 */
class CallbackController extends Controller
{
    /** What the visitor runs => the sign-up type it's filed under. */
    public const KINDS = ['hosts' => Registration::TYPE_HOST, 'business' => Registration::TYPE_BUSINESS, 'events' => Registration::TYPE_EVENT];

    public static function enabled(): bool
    {
        return filter_var(Setting::getValue('callbacks_enabled', '0'), FILTER_VALIDATE_BOOLEAN);
    }

    public function store(Request $request, SignupAlertService $alerts): JsonResponse
    {
        abort_unless(static::enabled(), 404);

        $key = 'callback:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json(['message' => 'Too many requests. Please try again in a few minutes, or WhatsApp us.'], 429);
        }

        $data = $request->validate([
            'phone' => ['required', 'string', 'max:20', function ($attribute, $value, $fail) {
                if (! static::kenyanMobile($value)) {
                    $fail('Enter a Kenyan mobile number: 9 digits after +254, e.g. 712 345 678.');
                }
            }],
            'kind' => ['required', 'in:'.implode(',', array_keys(self::KINDS))],
            'page' => ['nullable', 'string', 'max:40'],
        ]);
        RateLimiter::hit($key, 600);

        $phone = static::kenyanMobile($data['phone']);
        $type = self::KINDS[$data['kind']];
        $hero = collect(LandingPage::publicSections('home'))->firstWhere('section_key', 'hero');

        // An existing lead keeps everything it told us; the request is added to its answers.
        $registration = Registration::firstOrNew(['phone' => $phone, 'type' => $type]);
        $isNew = ! $registration->exists;
        $lastRequest = $registration->answers['callbackRequestedAt'] ?? null;
        if ($isNew) {
            $registration->fill([
                'first_name' => '',
                'last_name' => '',
                'property_type' => 'other',
                'status' => 'active',
                'source_page' => 'callback',
                'referral_source' => 'Call-back form',
                'consent_text' => Str::limit(strip_tags((string) ($hero['content']['callback_note'] ?? '')), 500, ''),
                'consented_at' => now(),
                'consent_ip' => $request->ip(),
            ]);
        }
        $registration->answers = array_merge($registration->answers ?? [], [
            'source' => 'callback',
            'callbackFor' => $data['kind'],
            'callbackRequestedAt' => now()->toIso8601String(),
        ]);
        $registration->save();

        // Announce new leads, and repeat requests at most once a day.
        if ($isNew || ! $lastRequest || now()->subDay()->greaterThan($lastRequest)) {
            defer(fn () => $alerts->notify($registration));
        }

        return response()->json(['message' => 'Thanks! We’ll be in touch.'], $isNew ? 201 : 200);
    }

    /** "+254712345678" for a Kenyan mobile (7XX or 1XX, 9 digits after +254), else null. */
    public static function kenyanMobile(?string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value);

        return preg_match('/^(?:254|0)?([17]\d{8})$/', $digits, $m) ? '+254'.$m[1] : null;
    }
}
